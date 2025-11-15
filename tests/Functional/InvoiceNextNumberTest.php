<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Trait\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceNextNumberTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testGetNextInvoiceNumberWithValidDate(): void
    {
        $token = $this->getAuthToken();

        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [
            'date' => '2025-11-15'
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('invoiceNumber', $responseData);
        $this->assertArrayHasKey('issueDate', $responseData);
        $this->assertArrayHasKey('format', $responseData);
        $this->assertEquals('2025-11-15', $responseData['issueDate']);
        $this->assertStringContainsString('2025', $responseData['invoiceNumber']);
        $this->assertStringContainsString('11', $responseData['invoiceNumber']);
    }

    public function testGetNextInvoiceNumberWithoutDate(): void
    {
        $token = $this->getAuthToken();

        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNextInvoiceNumberWithInvalidDate(): void
    {
        $token = $this->getAuthToken();

        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [
            'date' => 'invalid-date'
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNextInvoiceNumberRequiresAuthentication(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [
            'date' => '2025-11-15'
        ]);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testMonthBasedNumbering(): void
    {
        $token = $this->getAuthToken();

        // Get number for November 2025
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [
            'date' => '2025-11-15'
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $novemberResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        // Get number for December 2025
        $this->client->request(Request::METHOD_GET, '/api/invoices/next-number', [
            'date' => '2025-12-01'
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $decemberResponse = json_decode($this->client->getResponse()->getContent(), true);
        
        // Both should start with sequence 0001 since it's a new month
        $this->assertStringContainsString('0001', $novemberResponse['invoiceNumber']);
        $this->assertStringContainsString('0001', $decemberResponse['invoiceNumber']);
        $this->assertStringContainsString('11', $novemberResponse['invoiceNumber']);
        $this->assertStringContainsString('12', $decemberResponse['invoiceNumber']);
    }

    private function getAuthToken(): string
    {
        // Remove the ensureTestAdmin call since it's already done in setUp
        
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        return $response['token'];
    }
}