<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class InvoicePaymentApiTest extends WebTestCase
{
    use DatabaseTestTrait;

    private $client;
    private EntityManagerInterface $entityManager;
    private ?Company $testCustomer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin exists (admin has ROLE_B2B)
        $this->ensureTestAdmin();
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testPayInvoiceRequiresAuthentication(): void
    {
        $invoice = $this->createTestInvoice();
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testPayInvoiceRequiresB2BRole(): void
    {
        $regularUsername = $this->generateUniqueUsername('regularuser');
        $this->createTestUser($regularUsername, 'password123', ['ROLE_USER']);
        
        $regularUserToken = $this->getAuthToken($regularUsername, 'password123');
        $invoice = $this->createTestInvoice();
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPayInvoiceWithValidInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        $invoice = $this->createTestInvoice();
        
        // Verify the invoice is initially unpaid
        $this->assertFalse($invoice->isPaid());
        $this->assertEquals('issued', $invoice->getStatus()->value);
        $this->assertNull($invoice->getPaidAt());
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify response data
        $this->assertEquals($invoice->getId(), $responseData['id']);
        $this->assertTrue($responseData['isPaid']);
        $this->assertEquals('paid', $responseData['status']);
        $this->assertNotNull($responseData['paidAt']);
        
        // Verify database state
        $updatedInvoice = $this->entityManager->getRepository(Invoice::class)->find($invoice->getId());
        $this->assertTrue($updatedInvoice->isPaid());
        $this->assertEquals('paid', $updatedInvoice->getStatus()->value);
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedInvoice->getPaidAt());
    }

    public function testPayInvoiceWithCustomPaidDate(): void
    {
        $adminToken = $this->getAuthToken();
        $invoice = $this->createTestInvoice();
        
        $customPaidAt = '2024-01-15T10:30:00+00:00';
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'paidAt' => $customPaidAt
        ]));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify the custom paid date is used
        $this->assertTrue($responseData['isPaid']);
        $this->assertEquals('paid', $responseData['status']);
        $this->assertNotNull($responseData['paidAt']);
        
        // Verify the exact date was set (allowing for timezone conversion)
        $expectedDate = new \DateTime($customPaidAt);
        $actualDate = new \DateTime($responseData['paidAt']);
        $this->assertEquals($expectedDate->format('Y-m-d H:i:s'), $actualDate->format('Y-m-d H:i:s'));
    }

    public function testPayAlreadyPaidInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        $invoice = $this->createTestInvoice();
        
        // First, mark the invoice as paid
        $invoice->markAsPaid();
        $this->entityManager->flush();
        
        // Try to pay it again
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('detail', $responseData);
        $this->assertStringContainsString('already paid', $responseData['detail']);
    }

    public function testPayCancelledInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        $invoice = $this->createTestInvoice();
        
        // Cancel the invoice first
        $invoice->cancel();
        $this->entityManager->flush();
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('detail', $responseData);
        $this->assertStringContainsString('Cannot mark invoice with status cancelled as paid. Invoice must be issued first.', $responseData['detail']);
    }

    public function testPayNonExistentInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        $nonExistentId = 999999;
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$nonExistentId}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPayDeletedInvoice(): void
    {
        $adminToken = $this->getAuthToken();
        $invoice = $this->createDraftTestInvoice();
        
        // Soft delete the invoice 
        $invoice->softDelete();
        $this->entityManager->flush();
        
        $this->client->request(Request::METHOD_POST, "/api/invoices/{$invoice->getId()}/pay", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    private function getAuthToken(string $username = 'admin', string $password = 'admin123!'): string
    {
        // Use the same client instance, don't create a new one
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => $password
        ]));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        return $response['token'];
    }

    private function createTestCustomer(): Company
    {
        if ($this->testCustomer !== null) {
            return $this->testCustomer;
        }

        $customerName = $this->generateUniqueUsername('Test Customer');
        $this->testCustomer = new Company($customerName);
        $this->testCustomer->setTaxId($this->generateUniqueTaxId())
            ->setAddressLine1('Test Address 123')
            ->setCountryCode('PL')
            ->setEmail('test@example.com')
            ->setPhoneNumber('123456789');

        $this->entityManager->persist($this->testCustomer);
        $this->entityManager->flush();

        $this->createdEntities[] = $this->testCustomer;

        return $this->testCustomer;
    }

    private function createTestInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setNumber($this->generateUniqueInvoiceNumber())
            ->setIssueDate(new \DateTime('2024-01-01'))
            ->setSaleDate(new \DateTime('2024-01-01'))
            ->setDueDate(new \DateTime('2024-01-31'))
            ->setCurrency('PLN')
            ->setCustomer($this->createTestCustomer())
            ->setNotes('Test invoice for payment testing');

        // Add a test item
        $item = new InvoiceItem();
        $item->setDescription('Test Product')
            ->setQuantity('1.000')
            ->setUnit('szt.')
            ->setUnitPrice('100.00')
            ->setVatRate('23.00')
            ->setSortOrder(1);

        $invoice->addItem($item);

        // Calculate totals (simplified)
        $invoice->setSubtotal('100.00')
            ->setVatAmount('23.00')
            ->setTotal('123.00');

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->createdEntities[] = $invoice;

        return $invoice;
    }

    private function createDraftTestInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setNumber($this->generateUniqueInvoiceNumber())
            ->setIssueDate(new \DateTime('2024-01-01'))
            ->setSaleDate(new \DateTime('2024-01-01'))
            ->setDueDate(new \DateTime('2024-01-31'))
            ->setCurrency('PLN')
            ->setCustomer($this->createTestCustomer())
            ->setNotes('Test draft invoice for testing');

        // Set to draft using reflection to bypass the default ISSUED status
        $reflection = new \ReflectionClass($invoice);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $statusProperty->setValue($invoice, \App\Enum\InvoiceStatus::DRAFT);

        // Add a test item
        $item = new InvoiceItem();
        $item->setDescription('Test Product')
            ->setQuantity('1.000')
            ->setUnit('szt.')
            ->setUnitPrice('100.00')
            ->setVatRate('23.00')
            ->setSortOrder(1);

        $invoice->addItem($item);

        // Calculate totals (simplified)
        $invoice->setSubtotal('100.00')
            ->setVatAmount('23.00')
            ->setTotal('123.00');

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->createdEntities[] = $invoice;

        return $invoice;
    }

    private function generateUniqueInvoiceNumber(): string
    {
        return 'INV/TEST/' . uniqid() . '/' . date('Y');
    }

    private function generateUniqueTaxId(): string
    {
        return str_pad((string) mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }
}