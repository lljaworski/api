<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceItemTest extends TestCase
{
    private InvoiceItem $invoiceItem;
    private Invoice $invoice;

    protected function setUp(): void
    {
        $customer = new Company('Test Customer');
        $customer->setTaxId('1234567890');

        $this->invoice = new Invoice();
        $this->invoice->setNumber('INV/2024/001')
            ->setIssueDate(new \DateTime('2024-01-01'))
            ->setSaleDate(new \DateTime('2024-01-01'))
            ->setCustomer($customer);

        $this->invoiceItem = new InvoiceItem();
        $this->invoiceItem->setDescription('Test Product')
            ->setQuantity('2.000')
            ->setUnit('szt.')
            ->setUnitPrice('100.00')
            ->setVatRate('23.00')
            ->setSortOrder(1);
    }

    public function testInvoiceItemCreation(): void
    {
        $this->assertInstanceOf(InvoiceItem::class, $this->invoiceItem);
        $this->assertEquals('Test Product', $this->invoiceItem->getDescription());
        $this->assertEquals('2.000', $this->invoiceItem->getQuantity());
        $this->assertEquals('szt.', $this->invoiceItem->getUnit());
        $this->assertEquals('100.00', $this->invoiceItem->getUnitPrice());
        $this->assertEquals('23.00', $this->invoiceItem->getVatRate());
        $this->assertEquals(1, $this->invoiceItem->getSortOrder());
        
        // Amounts are automatically calculated when setting fields
        // 2.000 * 100.00 = 200.00 net, 200.00 * 0.23 = 46.00 VAT, 246.00 gross
        $this->assertEquals('200.00', $this->invoiceItem->getNetAmount());
        $this->assertEquals('46.00', $this->invoiceItem->getVatAmount());
        $this->assertEquals('246.00', $this->invoiceItem->getGrossAmount());
    }

    public function testAmountCalculations(): void
    {
        $this->invoiceItem->recalculateAmounts();

        // 2.000 * 100.00 = 200.00 net
        $this->assertEquals('200.00', $this->invoiceItem->getNetAmount());
        
        // 200.00 * 0.23 = 46.00 VAT
        $this->assertEquals('46.00', $this->invoiceItem->getVatAmount());
        
        // 200.00 + 46.00 = 246.00 gross
        $this->assertEquals('246.00', $this->invoiceItem->getGrossAmount());
    }

    public function testDifferentVatRates(): void
    {
        // Test 0% VAT
        $this->invoiceItem->setVatRate('0.00')->recalculateAmounts();
        $this->assertEquals('200.00', $this->invoiceItem->getNetAmount());
        $this->assertEquals('0.00', $this->invoiceItem->getVatAmount());
        $this->assertEquals('200.00', $this->invoiceItem->getGrossAmount());

        // Test 5% VAT
        $this->invoiceItem->setVatRate('5.00')->recalculateAmounts();
        $this->assertEquals('200.00', $this->invoiceItem->getNetAmount());
        $this->assertEquals('10.00', $this->invoiceItem->getVatAmount());
        $this->assertEquals('210.00', $this->invoiceItem->getGrossAmount());

        // Test 8% VAT
        $this->invoiceItem->setVatRate('8.00')->recalculateAmounts();
        $this->assertEquals('200.00', $this->invoiceItem->getNetAmount());
        $this->assertEquals('16.00', $this->invoiceItem->getVatAmount());
        $this->assertEquals('216.00', $this->invoiceItem->getGrossAmount());
    }

    public function testDecimalQuantityCalculations(): void
    {
        $this->invoiceItem->setQuantity('1.500')
            ->setUnitPrice('33.33')
            ->setVatRate('23.00')
            ->recalculateAmounts();

        // 1.500 * 33.33 = 49.995 -> 49.99 (bcmul with precision 2)
        $this->assertEquals('49.99', $this->invoiceItem->getNetAmount());
        
        // 49.99 * 0.23 = 11.4977 -> 11.49 VAT (bcmul with precision 2)
        $this->assertEquals('11.49', $this->invoiceItem->getVatAmount());
        
        // 49.99 + 11.49 = 61.48 gross
        $this->assertEquals('61.48', $this->invoiceItem->getGrossAmount());
    }

    public function testAmountRecalculationOnFieldChanges(): void
    {
        $this->invoiceItem->recalculateAmounts();
        $originalNetAmount = $this->invoiceItem->getNetAmount();

        // Change quantity - should trigger recalculation
        $this->invoiceItem->setQuantity('3.000');
        $this->assertEquals('300.00', $this->invoiceItem->getNetAmount());
        $this->assertNotEquals($originalNetAmount, $this->invoiceItem->getNetAmount());

        // Change unit price - should trigger recalculation
        $this->invoiceItem->setUnitPrice('150.00');
        $this->assertEquals('450.00', $this->invoiceItem->getNetAmount());

        // Change VAT rate - should trigger recalculation
        $originalVatAmount = $this->invoiceItem->getVatAmount();
        $this->invoiceItem->setVatRate('8.00');
        $this->assertEquals('36.00', $this->invoiceItem->getVatAmount());
        $this->assertNotEquals($originalVatAmount, $this->invoiceItem->getVatAmount());
    }

    public function testInvoiceRelationship(): void
    {
        $this->assertNull($this->invoiceItem->getInvoice());

        $this->invoiceItem->setInvoice($this->invoice);
        $this->assertEquals($this->invoice, $this->invoiceItem->getInvoice());

        $this->invoiceItem->setInvoice(null);
        $this->assertNull($this->invoiceItem->getInvoice());
    }

    public function testInvoiceTotalRecalculationWhenItemChanged(): void
    {
        $this->invoice->addItem($this->invoiceItem);
        $this->invoiceItem->recalculateAmounts();

        // Invoice should have recalculated totals
        $this->assertEquals('200.00', $this->invoice->getSubtotal());
        $this->assertEquals('46.00', $this->invoice->getVatAmount());
        $this->assertEquals('246.00', $this->invoice->getTotal());

        // Change item and verify invoice totals update
        $this->invoiceItem->setQuantity('1.000');
        
        // Need to manually trigger invoice recalculation in this test
        $this->invoice->recalculateTotals();
        $this->assertEquals('100.00', $this->invoice->getSubtotal());
        $this->assertEquals('23.00', $this->invoice->getVatAmount());
        $this->assertEquals('123.00', $this->invoice->getTotal());
    }

    public function testKsefMethods(): void
    {
        $this->invoiceItem->recalculateAmounts();

        $this->assertEquals('200.00', $this->invoiceItem->getKsefP11());
        $this->assertEquals(23, $this->invoiceItem->getKsefP12());
        $this->assertEquals('Test Product', $this->invoiceItem->getKsefP7());
        $this->assertEquals('szt.', $this->invoiceItem->getKsefP8A());
        $this->assertEquals('2.000', $this->invoiceItem->getKsefP8B());
        $this->assertEquals('100.00', $this->invoiceItem->getKsefP9A());
    }

    public function testIsCompleteValidation(): void
    {
        // Item with all required fields should be complete
        $this->assertTrue($this->invoiceItem->isComplete());

        // Item without description should not be complete
        $incompleteItem = new InvoiceItem();
        $this->assertFalse($incompleteItem->isComplete());

        $incompleteItem->setDescription('Product');
        $this->assertFalse($incompleteItem->isComplete());

        $incompleteItem->setQuantity('1.000');
        $this->assertFalse($incompleteItem->isComplete());

        $incompleteItem->setUnit('szt.');
        $this->assertFalse($incompleteItem->isComplete());

        $incompleteItem->setUnitPrice('50.00');
        $this->assertFalse($incompleteItem->isComplete());

        $incompleteItem->setVatRate('23.00');
        $this->assertTrue($incompleteItem->isComplete());
    }

    public function testGetDisplayString(): void
    {
        $expected = 'Test Product (2.000 szt. × 100.00 PLN, VAT 23.00%)';
        $this->assertEquals($expected, $this->invoiceItem->getDisplayString());

        // Test with different values
        $this->invoiceItem->setDescription('Custom Service')
            ->setQuantity('1.500')
            ->setUnit('godz.')
            ->setUnitPrice('75.50')
            ->setVatRate('8.00');

        $expected = 'Custom Service (1.500 godz. × 75.50 PLN, VAT 8.00%)';
        $this->assertEquals($expected, $this->invoiceItem->getDisplayString());
    }

    public function testValidUnits(): void
    {
        $validUnits = ['szt.', 'kg', 'm', 'm2', 'm3', 'godz.', 'dzień', 'l', 't', 'km', 'kWh', 'usł.', 'kpl.', 'op.', 'm.b.'];
        
        foreach ($validUnits as $unit) {
            $this->invoiceItem->setUnit($unit);
            $this->assertEquals($unit, $this->invoiceItem->getUnit());
        }
    }

    public function testValidVatRates(): void
    {
        $validRates = ['0.00', '5.00', '8.00', '23.00'];
        
        foreach ($validRates as $rate) {
            $this->invoiceItem->setVatRate($rate);
            $this->assertEquals($rate, $this->invoiceItem->getVatRate());
        }
    }

    public function testSortOrderHandling(): void
    {
        $this->invoiceItem->setSortOrder(5);
        $this->assertEquals(5, $this->invoiceItem->getSortOrder());

        $this->invoiceItem->setSortOrder(0);
        $this->assertEquals(0, $this->invoiceItem->getSortOrder());
    }

    public function testAmountSettersAndGetters(): void
    {
        // Test direct amount setters (normally these would be calculated)
        $this->invoiceItem->setNetAmount('150.00');
        $this->assertEquals('150.00', $this->invoiceItem->getNetAmount());

        $this->invoiceItem->setVatAmount('34.50');
        $this->assertEquals('34.50', $this->invoiceItem->getVatAmount());

        $this->invoiceItem->setGrossAmount('184.50');
        $this->assertEquals('184.50', $this->invoiceItem->getGrossAmount());
    }

    public function testEmptyFieldsDoNotTriggerCalculation(): void
    {
        $emptyItem = new InvoiceItem();
        
        // Setting only some fields should not trigger calculation
        $emptyItem->setDescription('Test');
        $emptyItem->recalculateAmounts();
        $this->assertEquals('0.00', $emptyItem->getNetAmount());

        $emptyItem->setQuantity('2.000');
        $emptyItem->recalculateAmounts();
        $this->assertEquals('0.00', $emptyItem->getNetAmount());

        // Only when all required fields are set should calculation work
        $emptyItem->setUnitPrice('100.00')
            ->setVatRate('23.00')
            ->recalculateAmounts();
        $this->assertEquals('200.00', $emptyItem->getNetAmount());
    }

    public function testPrecisionInCalculations(): void
    {
        // Test with values that could cause precision issues
        $this->invoiceItem->setQuantity('3.333')
            ->setUnitPrice('99.99')
            ->setVatRate('23.00')
            ->recalculateAmounts();

        // 3.333 * 99.99 = 333.29667 -> 333.26 (bcmul with precision 2)
        $this->assertEquals('333.26', $this->invoiceItem->getNetAmount());
        
        // 333.26 * 0.23 = 76.6498 -> 76.64 (bcmul with precision 2)
        $this->assertEquals('76.64', $this->invoiceItem->getVatAmount());
        
        // 333.26 + 76.64 = 409.90
        $this->assertEquals('409.90', $this->invoiceItem->getGrossAmount());
    }
}