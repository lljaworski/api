<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\Command\Invoice\PayInvoiceCommand;
use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Doctrine\ORM\EntityManagerInterface;

class PayInvoiceCqrsTest extends WebTestCase
{
    use DatabaseTestTrait;

    private MessageBusInterface $commandBus;
    private EntityManagerInterface $entityManager;
    private ?Company $testCustomer = null;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->commandBus = static::getContainer()->get('command.bus');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Create test customer
        $this->createTestCustomer();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testPayInvoiceCommandWithValidInvoice(): void
    {
        $invoice = $this->createTestInvoice();
        
        // Verify initial state
        $this->assertFalse($invoice->isPaid());
        $this->assertEquals('issued', $invoice->getStatus()->value);
        $this->assertNull($invoice->getPaidAt());
        
        $command = new PayInvoiceCommand(id: $invoice->getId());
        
        $beforePayment = new \DateTime();
        $envelope = $this->commandBus->dispatch($command);
        $result = $envelope->last(HandledStamp::class)->getResult();
        
        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($invoice->getId(), $result->getId());
        
        // Verify the invoice was marked as paid
        $this->assertTrue($result->isPaid());
        $this->assertEquals('paid', $result->getStatus()->value);
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getPaidAt());
        $this->assertGreaterThanOrEqual($beforePayment, $result->getPaidAt());
    }

    public function testPayInvoiceCommandWithCustomPaidDate(): void
    {
        $invoice = $this->createTestInvoice();
        
        $customPaidDate = new \DateTime('2024-01-15 10:30:00');
        $command = new PayInvoiceCommand(
            id: $invoice->getId(),
            paidAt: $customPaidDate
        );
        
        $envelope = $this->commandBus->dispatch($command);
        $result = $envelope->last(HandledStamp::class)->getResult();
        
        $this->assertTrue($result->isPaid());
        $this->assertEquals('paid', $result->getStatus()->value);
        $this->assertEquals($customPaidDate->format('Y-m-d H:i:s'), $result->getPaidAt()->format('Y-m-d H:i:s'));
    }

    public function testPayInvoiceCommandWithNonExistentInvoice(): void
    {
        $this->expectException(\Symfony\Component\Messenger\Exception\HandlerFailedException::class);
        $this->expectExceptionMessage('Invoice not found');
        
        $command = new PayInvoiceCommand(id: 999999);
        $this->commandBus->dispatch($command);
    }

    public function testPayInvoiceCommandWithDeletedInvoice(): void
    {
        // Create a draft invoice that can be deleted
        $invoice = $this->createDraftTestInvoice();
        $invoice->softDelete();
        $this->entityManager->flush();
        
        $this->expectException(\Symfony\Component\Messenger\Exception\HandlerFailedException::class);
        $this->expectExceptionMessage('Invoice not found');
        
        $command = new PayInvoiceCommand(id: $invoice->getId());
        $this->commandBus->dispatch($command);
    }

    public function testPayInvoiceCommandWithAlreadyPaidInvoice(): void
    {
        $invoice = $this->createTestInvoice();
        
        // Mark the invoice as paid first
        $invoice->markAsPaid();
        $this->entityManager->flush();
        
        $this->expectException(\Symfony\Component\Messenger\Exception\HandlerFailedException::class);
        $this->expectExceptionMessage('Invoice is already paid');
        
        $command = new PayInvoiceCommand(id: $invoice->getId());
        $this->commandBus->dispatch($command);
    }

    public function testPayInvoiceCommandWithCancelledInvoice(): void
    {
        $invoice = $this->createTestInvoice();
        
        // Cancel the invoice
        $invoice->cancel();
        $this->entityManager->flush();
        
        $this->expectException(\Symfony\Component\Messenger\Exception\HandlerFailedException::class);
        $this->expectExceptionMessage('Cannot mark invoice with status cancelled as paid. Invoice must be issued first.');
        
        $command = new PayInvoiceCommand(id: $invoice->getId());
        $this->commandBus->dispatch($command);
    }

    public function testPayInvoiceCommandWithDraftInvoice(): void
    {
        $invoice = $this->createDraftTestInvoice();
        
        // Business rule: Draft invoices cannot be directly marked as paid
        // They should be issued first
        $this->expectException(\Symfony\Component\Messenger\Exception\HandlerFailedException::class);
        $this->expectExceptionMessage('Cannot mark invoice with status draft as paid. Invoice must be issued first.');
        
        $command = new PayInvoiceCommand(id: $invoice->getId());
        $this->commandBus->dispatch($command);
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