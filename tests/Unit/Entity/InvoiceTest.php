<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceStatus;
use App\Enum\PaymentMethodEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceTest extends TestCase
{
    private Invoice $invoice;
    private Company $customer;

    protected function setUp(): void
    {
        $this->customer = new Company('Test Customer');
        $this->customer->setTaxId('1234567890')
            ->setAddressLine1('Test Address 123')
            ->setCountryCode('PL')
            ->setEmail('test@example.com')
            ->setPhoneNumber('123456789');

        $this->invoice = new Invoice();
        $this->invoice->setNumber('INV/2024/001')
            ->setIssueDate(new \DateTime('2024-01-01'))
            ->setSaleDate(new \DateTime('2024-01-01'))
            ->setCustomer($this->customer);
    }

    public function testInvoiceCreation(): void
    {
        $this->assertInstanceOf(Invoice::class, $this->invoice);
        $this->assertEquals('INV/2024/001', $this->invoice->getNumber());
        $this->assertEquals(InvoiceStatus::DRAFT, $this->invoice->getStatus());
        $this->assertFalse($this->invoice->isPaid());
        $this->assertEquals('PLN', $this->invoice->getCurrency());
        $this->assertEquals('0.00', $this->invoice->getSubtotal());
        $this->assertEquals('0.00', $this->invoice->getVatAmount());
        $this->assertEquals('0.00', $this->invoice->getTotal());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->invoice->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->invoice->getUpdatedAt());
    }

    public function testStatusTransitions(): void
    {
        // Test valid transitions from DRAFT
        $this->invoice->setStatus(InvoiceStatus::ISSUED);
        $this->assertEquals(InvoiceStatus::ISSUED, $this->invoice->getStatus());

        $this->invoice->setStatus(InvoiceStatus::PAID);
        $this->assertEquals(InvoiceStatus::PAID, $this->invoice->getStatus());

        // Test invalid transition from PAID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from paid to cancelled');
        $this->invoice->setStatus(InvoiceStatus::CANCELLED);
    }

    public function testBusinessLogicMethods(): void
    {
        // Test draft invoice can be edited and deleted
        $this->assertTrue($this->invoice->canBeEdited());
        $this->assertTrue($this->invoice->canBeDeleted());

        // Test issued invoice cannot be edited but can be deleted
        $this->invoice->setStatus(InvoiceStatus::ISSUED);
        $this->assertFalse($this->invoice->canBeEdited());
        $this->assertFalse($this->invoice->canBeDeleted());

        // Test cancelled invoice can be deleted
        $this->invoice = new Invoice();
        $this->invoice->setNumber('INV/2024/002')
            ->setIssueDate(new \DateTime())
            ->setSaleDate(new \DateTime())
            ->setCustomer($this->customer)
            ->setStatus(InvoiceStatus::CANCELLED);
        $this->assertTrue($this->invoice->canBeDeleted());
    }

    public function testIssueInvoice(): void
    {
        $this->invoice->issue();
        $this->assertEquals(InvoiceStatus::ISSUED, $this->invoice->getStatus());
    }

    public function testMarkAsPaid(): void
    {
        $this->invoice->setStatus(InvoiceStatus::ISSUED);
        $beforePaid = new \DateTime();
        
        $this->invoice->markAsPaid();
        
        $this->assertEquals(InvoiceStatus::PAID, $this->invoice->getStatus());
        $this->assertTrue($this->invoice->isPaid());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->invoice->getPaidAt());
        $this->assertGreaterThanOrEqual($beforePaid, $this->invoice->getPaidAt());
    }

    public function testCancelInvoice(): void
    {
        $this->invoice->cancel();
        $this->assertEquals(InvoiceStatus::CANCELLED, $this->invoice->getStatus());
    }

    public function testOverdueLogic(): void
    {
        // Not overdue - no due date
        $this->assertFalse($this->invoice->isOverdue());

        // Not overdue - future due date
        $this->invoice->setStatus(InvoiceStatus::ISSUED)
            ->setDueDate(new \DateTime('+7 days'));
        $this->assertFalse($this->invoice->isOverdue());

        // Overdue - past due date
        $this->invoice->setDueDate(new \DateTime('-1 day'));
        $this->assertTrue($this->invoice->isOverdue());

        // Not overdue - already paid
        $this->invoice->setIsPaid(true);
        $this->assertFalse($this->invoice->isOverdue());

        // Not overdue - draft status (create new invoice to avoid invalid transition)
        $draftInvoice = new Invoice();
        $draftInvoice->setNumber('INV/DRAFT/001')
            ->setIssueDate(new \DateTime())
            ->setSaleDate(new \DateTime())
            ->setCustomer($this->customer)
            ->setDueDate(new \DateTime('-1 day')); // Past due date
        
        // Draft invoices are never overdue
        $this->assertFalse($draftInvoice->isOverdue());
    }

    public function testSoftDelete(): void
    {
        $this->assertFalse($this->invoice->isDeleted());
        $this->assertTrue($this->invoice->isActive());
        $this->assertNull($this->invoice->getDeletedAt());

        $beforeDelete = new \DateTime();
        $this->invoice->softDelete();

        $this->assertTrue($this->invoice->isDeleted());
        $this->assertFalse($this->invoice->isActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->invoice->getDeletedAt());
        $this->assertGreaterThanOrEqual($beforeDelete, $this->invoice->getDeletedAt());
    }

    public function testSoftDeleteFailsForIssuedInvoice(): void
    {
        $this->invoice->setStatus(InvoiceStatus::ISSUED);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete invoice with status issued');
        $this->invoice->softDelete();
    }

    public function testRestore(): void
    {
        $this->invoice->softDelete();
        $this->assertTrue($this->invoice->isDeleted());

        $this->invoice->restore();
        $this->assertFalse($this->invoice->isDeleted());
        $this->assertTrue($this->invoice->isActive());
        $this->assertNull($this->invoice->getDeletedAt());
    }

    public function testAddAndRemoveItems(): void
    {
        $item1 = new InvoiceItem();
        $item1->setDescription('Test Item 1')
            ->setQuantity('2.000')
            ->setUnit('szt.')
            ->setUnitPrice('100.00')
            ->setVatRate('23.00');

        $item2 = new InvoiceItem();
        $item2->setDescription('Test Item 2')
            ->setQuantity('1.000')
            ->setUnit('szt.')
            ->setUnitPrice('50.00')
            ->setVatRate('8.00');

        $this->assertEquals(0, $this->invoice->getItems()->count());

        // Add items
        $this->invoice->addItem($item1);
        $this->assertEquals(1, $this->invoice->getItems()->count());
        $this->assertEquals($this->invoice, $item1->getInvoice());

        $this->invoice->addItem($item2);
        $this->assertEquals(2, $this->invoice->getItems()->count());

        // Try to add same item again - should not duplicate
        $this->invoice->addItem($item1);
        $this->assertEquals(2, $this->invoice->getItems()->count());

        // Remove item
        $this->invoice->removeItem($item1);
        $this->assertEquals(1, $this->invoice->getItems()->count());
        $this->assertNull($item1->getInvoice());

        // Remove non-existing item - should not cause error
        $item3 = new InvoiceItem();
        $this->invoice->removeItem($item3);
        $this->assertEquals(1, $this->invoice->getItems()->count());
    }

    public function testRecalculateTotals(): void
    {
        $item1 = new InvoiceItem();
        $item1->setDescription('Test Item 1')
            ->setQuantity('2.000')
            ->setUnit('szt.')
            ->setUnitPrice('100.00')
            ->setVatRate('23.00');

        $item2 = new InvoiceItem();
        $item2->setDescription('Test Item 2')
            ->setQuantity('1.000')
            ->setUnit('szt.')
            ->setUnitPrice('50.00')
            ->setVatRate('8.00');

        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);

        // Manually recalculate item amounts first
        $item1->recalculateAmounts();
        $item2->recalculateAmounts();

        $this->invoice->recalculateTotals();

        // Item 1: 2 * 100.00 = 200.00 net, 200.00 * 0.23 = 46.00 VAT
        // Item 2: 1 * 50.00 = 50.00 net, 50.00 * 0.08 = 4.00 VAT
        // Total: 250.00 net, 50.00 VAT, 300.00 gross

        $this->assertEquals('250.00', $this->invoice->getSubtotal());
        $this->assertEquals('50.00', $this->invoice->getVatAmount());
        $this->assertEquals('300.00', $this->invoice->getTotal());
    }

    public function testPaymentDateAutomaticSetting(): void
    {
        $this->assertNull($this->invoice->getPaidAt());

        $beforePaid = new \DateTime();
        $this->invoice->setIsPaid(true);

        $this->assertTrue($this->invoice->isPaid());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->invoice->getPaidAt());
        $this->assertGreaterThanOrEqual($beforePaid, $this->invoice->getPaidAt());

        // Unpaying should clear the paid date
        $this->invoice->setIsPaid(false);
        $this->assertFalse($this->invoice->isPaid());
        $this->assertNull($this->invoice->getPaidAt());
    }

    public function testUpdatedAtTouchOnChanges(): void
    {
        $originalUpdatedAt = $this->invoice->getUpdatedAt();
        
        // Wait a moment to ensure time difference
        usleep(1000);
        
        $this->invoice->setNotes('Updated notes');
        $this->assertGreaterThan($originalUpdatedAt, $this->invoice->getUpdatedAt());
    }

    public function testCurrencyValidation(): void
    {
        $validCurrencies = ['PLN', 'EUR', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK'];
        
        foreach ($validCurrencies as $currency) {
            $this->invoice->setCurrency($currency);
            $this->assertEquals($currency, $this->invoice->getCurrency());
        }
    }

    public function testSetDueDateWithNull(): void
    {
        $dueDate = new \DateTime('+30 days');
        $this->invoice->setDueDate($dueDate);
        $this->assertEquals($dueDate, $this->invoice->getDueDate());

        $this->invoice->setDueDate(null);
        $this->assertNull($this->invoice->getDueDate());
    }

    public function testPaymentMethodHandling(): void
    {
        $this->assertNull($this->invoice->getPaymentMethod());

        $this->invoice->setPaymentMethod(PaymentMethodEnum::DIGITAL_WALLETS);
        $this->assertEquals(PaymentMethodEnum::DIGITAL_WALLETS, $this->invoice->getPaymentMethod());

        $this->invoice->setPaymentMethod(null);
        $this->assertNull($this->invoice->getPaymentMethod());
    }

    public function testKsefFields(): void
    {
        $this->assertNull($this->invoice->getKsefNumber());
        $this->assertNull($this->invoice->getKsefSubmittedAt());

        $ksefNumber = 'KSEF-123456789';
        $ksefDate = new \DateTime();

        $this->invoice->setKsefNumber($ksefNumber);
        $this->invoice->setKsefSubmittedAt($ksefDate);

        $this->assertEquals($ksefNumber, $this->invoice->getKsefNumber());
        $this->assertEquals($ksefDate, $this->invoice->getKsefSubmittedAt());
    }

    public function testCustomerRelationship(): void
    {
        $newCustomer = new Company('New Customer');
        $newCustomer->setTaxId('9876543210')
            ->setAddressLine1('New Address 456')
            ->setCountryCode('PL')
            ->setEmail('new@example.com')
            ->setPhoneNumber('987654321');

        $this->invoice->setCustomer($newCustomer);
        $this->assertEquals($newCustomer, $this->invoice->getCustomer());
        $this->assertEquals('New Customer', $this->invoice->getCustomer()->getName());
    }
}