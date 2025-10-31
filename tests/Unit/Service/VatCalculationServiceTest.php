<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\VatCalculationService;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Company;
use PHPUnit\Framework\TestCase;

class VatCalculationServiceTest extends TestCase
{
    private VatCalculationService $service;
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->service = new VatCalculationService();
        
        $customer = new Company('Test Customer');
        $this->invoice = new Invoice();
        $this->invoice->setNumber('FV/2024/10/0001');
        $this->invoice->setIssueDate(new \DateTime('2024-10-01'));
        $this->invoice->setSaleDate(new \DateTime('2024-10-01'));
        $this->invoice->setCurrency('PLN');
        $this->invoice->setCustomer($customer);
    }

    public function testRecalculateInvoiceItem(): void
    {
        $item = new InvoiceItem();
        $item->setDescription('Test Product');
        $item->setQuantity('2.000');
        $item->setUnitPrice('100.00');
        $item->setVatRate('23.00');
        $item->setUnit('szt.');

        $this->service->recalculateInvoiceItem($item);

        // 2 × 100.00 = 200.00 net
        $this->assertEquals('200.00', $item->getNetAmount());
        
        // 200.00 × 0.23 = 46.00 VAT
        $this->assertEquals('46.00', $item->getVatAmount());
        
        // 200.00 + 46.00 = 246.00 gross
        $this->assertEquals('246.00', $item->getGrossAmount());
    }

    public function testCalculateItemTotals(): void
    {
        $totals = $this->service->calculateItemTotals('2.000', '100.00', '23.00');

        $this->assertEquals('200.00', $totals['netAmount']);
        $this->assertEquals('46.00', $totals['vatAmount']);
        $this->assertEquals('246.00', $totals['grossAmount']);
    }

    public function testCalculateItemTotalsWithZeroVat(): void
    {
        $totals = $this->service->calculateItemTotals('1.000', '50.00', '0.00');

        $this->assertEquals('50.00', $totals['netAmount']);
        $this->assertEquals('0.00', $totals['vatAmount']);
        $this->assertEquals('50.00', $totals['grossAmount']);
    }

    public function testCalculateItemTotalsWithHighPrecision(): void
    {
        $service = new VatCalculationService();
        
        // Test case that produces specific bcmath results with 2 decimal precision
        $result = $service->calculateItemTotals('3.33', '33.33', '0.00');
        
        $this->assertEquals('110.98', $result['netAmount']); // 3.33 * 33.33 = 110.9889 rounded to 110.98
        $this->assertEquals('0.00', $result['vatAmount']);
        $this->assertEquals('110.98', $result['grossAmount']);
    }

    public function testCalculateInvoiceTotals(): void
    {
        $item1 = new InvoiceItem();
        $item1->setDescription('Product 1');
        $item1->setQuantity('2.000');
        $item1->setUnitPrice('100.00');
        $item1->setVatRate('23.00');
        $item1->setUnit('szt.');
        $item1->setSortOrder(1);

        $item2 = new InvoiceItem();
        $item2->setDescription('Product 2');
        $item2->setQuantity('1.000');
        $item2->setUnitPrice('50.00');
        $item2->setVatRate('8.00');
        $item2->setUnit('szt.');
        $item2->setSortOrder(2);

        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);

        // First calculate item amounts
        foreach ($this->invoice->getItems() as $item) {
            $this->service->recalculateInvoiceItem($item);
        }
        
        $this->service->recalculateInvoiceTotals($this->invoice);

        // Item 1: 200.00 net, 46.00 VAT
        // Item 2: 50.00 net, 4.00 VAT
        // Total: 250.00 net, 50.00 VAT, 300.00 gross
        $this->assertEquals('250.00', $this->invoice->getSubtotal());
        $this->assertEquals('50.00', $this->invoice->getVatAmount());
        $this->assertEquals('300.00', $this->invoice->getTotal());
    }

    public function testCalculateInvoiceTotalsWithNoItems(): void
    {
        $this->service->calculateInvoiceTotals($this->invoice);

        $this->assertEquals('0.00', $this->invoice->getSubtotal());
        $this->assertEquals('0.00', $this->invoice->getVatAmount());
        $this->assertEquals('0.00', $this->invoice->getTotal());
    }

    public function testCalculateInvoiceTotalsWithMixedVatRates(): void
    {
        $items = [
            ['quantity' => '1.000', 'price' => '100.00', 'vat' => '0.00'],   // Exempt
            ['quantity' => '2.000', 'price' => '50.00', 'vat' => '5.00'],    // Reduced
            ['quantity' => '1.000', 'price' => '200.00', 'vat' => '8.00'],   // Reduced
            ['quantity' => '3.000', 'price' => '150.00', 'vat' => '23.00'],  // Standard
        ];

        foreach ($items as $i => $itemData) {
            $item = new InvoiceItem();
            $item->setDescription("Product " . ($i + 1));
            $item->setQuantity($itemData['quantity']);
            $item->setUnitPrice($itemData['price']);
            $item->setVatRate($itemData['vat']);
            $item->setUnit('szt.');
            $item->setSortOrder($i + 1);

            // Recalculate item totals first
            $this->service->recalculateInvoiceItem($item);
            $this->invoice->addItem($item);
        }

        // Recalculate invoice totals (this updates the invoice entity)
        $this->service->recalculateInvoiceTotals($this->invoice);

        // Item 1: 100.00 net, 0.00 VAT
        // Item 2: 100.00 net, 5.00 VAT
        // Item 3: 200.00 net, 16.00 VAT
        // Item 4: 450.00 net, 103.50 VAT
        // Total: 850.00 net, 124.50 VAT, 974.50 gross
        $this->assertEquals('850.00', $this->invoice->getSubtotal());
        $this->assertEquals('124.50', $this->invoice->getVatAmount());
        $this->assertEquals('974.50', $this->invoice->getTotal());
    }

    public function testGetKsefTotals(): void
    {
        $item1 = new InvoiceItem();
        $item1->setDescription('Product 1');
        $item1->setQuantity('2.000');
        $item1->setUnitPrice('100.00');
        $item1->setVatRate('23.00');
        $item1->setUnit('szt.');
        $item1->setSortOrder(1);

        $item2 = new InvoiceItem();
        $item2->setDescription('Product 2');
        $item2->setQuantity('1.000');
        $item2->setUnitPrice('50.00');
        $item2->setVatRate('8.00');
        $item2->setUnit('szt.');
        $item2->setSortOrder(2);

        // Recalculate item totals first
        $this->service->recalculateInvoiceItem($item1);
        $this->service->recalculateInvoiceItem($item2);
        
        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);

        $ksefTotals = $this->service->getKsefTotals($this->invoice);

        $this->assertIsArray($ksefTotals);
        
        // Check KSeF field P_13_1 (net amount for 23% VAT)
        $this->assertEquals('200.00', $ksefTotals['P_13_1']);
        
        // Check KSeF field P_14_1 (VAT amount for 23% VAT)  
        $this->assertEquals('46.00', $ksefTotals['P_14_1']);
        
        // Check KSeF field P_13_2 (net amount for 8% VAT)
        $this->assertEquals('50.00', $ksefTotals['P_13_2']);
        
        // Check KSeF field P_14_2 (VAT amount for 8% VAT)
        $this->assertEquals('4.00', $ksefTotals['P_14_2']);
        
        // Check KSeF field P_15 (total invoice amount)
        $this->assertEquals('300.00', $ksefTotals['P_15']);
    }

    public function testIsValidVatRate(): void
    {
        $validRates = ['0.00', '5.00', '8.00', '23.00'];
        
        foreach ($validRates as $rate) {
            $this->assertTrue($this->service->isValidVatRate($rate));
        }
    }

    public function testInvalidVatRates(): void
    {
        $invalidRates = ['10.00', '15.00', '20.00', '25.00', '-5.00', '100.00'];
        
        foreach ($invalidRates as $rate) {
            $this->assertFalse($this->service->isValidVatRate($rate));
        }
    }

    public function testGetAvailableVatRates(): void
    {
        $rates = $this->service->getAvailableVatRates();
        
        $this->assertIsArray($rates);
        $this->assertContains('0.00', $rates);
        $this->assertContains('5.00', $rates);
        $this->assertContains('8.00', $rates);
        $this->assertContains('23.00', $rates);
        $this->assertCount(4, $rates);
    }

    public function testInvalidVatRateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid VAT rate: 15.00');
        
        $this->service->calculateItemTotals('1.000', '100.00', '15.00');
    }

    public function testFormatAmount(): void
    {
        $testCases = [
            ['100.00', 'PLN', '100,00 PLN'],
            ['1234.56', 'EUR', '1 234,56 EUR'],
            ['0.00', 'USD', '0,00 USD'],
        ];

        foreach ($testCases as [$amount, $currency, $expected]) {
            $formatted = $this->service->formatAmount($amount, $currency);
            $this->assertEquals($expected, $formatted);
        }
    }

    public function testPercentageToDecimal(): void
    {
        $this->assertEquals('0.2300', $this->service->percentageToDecimal('23.00'));
        $this->assertEquals('0.0800', $this->service->percentageToDecimal('8.00'));
        $this->assertEquals('0.0000', $this->service->percentageToDecimal('0.00'));
    }

    public function testDecimalToPercentage(): void
    {
        $this->assertEquals('23.00', $this->service->decimalToPercentage('0.23'));
        $this->assertEquals('8.00', $this->service->decimalToPercentage('0.08'));
        $this->assertEquals('0.00', $this->service->decimalToPercentage('0.00'));
    }

    public function testApplyDiscount(): void
    {
        $result = $this->service->applyDiscount('100.00', '10.00');
        
        $this->assertEquals('100.00', $result['originalAmount']);
        $this->assertEquals('10.00', $result['discountPercentage']);
        $this->assertEquals('10.00', $result['discountAmount']);
        $this->assertEquals('90.00', $result['finalAmount']);
    }

    public function testValidateInvoiceTotals(): void
    {
        // Create item and calculate properly
        $item = new InvoiceItem();
        $item->setDescription('Test Product');
        $item->setQuantity('2.000');
        $item->setUnitPrice('100.00');
        $item->setVatRate('23.00');
        $item->setUnit('szt.');
        $item->setSortOrder(1);

        $this->invoice->addItem($item);
        $this->service->recalculateInvoiceItem($item);
        $this->service->recalculateInvoiceTotals($this->invoice);

        $validation = $this->service->validateInvoiceTotals($this->invoice);
        
        $this->assertTrue($validation['isValid']);
        $this->assertEmpty($validation['errors']);
        $this->assertArrayHasKey('calculatedTotals', $validation);
    }
}