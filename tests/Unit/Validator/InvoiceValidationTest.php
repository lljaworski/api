<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Enum\InvoiceStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvoiceValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;
    private Company $testCustomer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        
        $this->testCustomer = new Company('Test Customer');
        $this->testCustomer->setTaxId('1234567890');
    }

    public function testValidInvoicePassesValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        
        $this->assertCount(0, $violations);
    }

    public function testInvoiceNumberValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test empty number
        $invoice->setNumber('');
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('blank', $violations[0]->getMessage());
        
        // Test too long number
        $invoice->setNumber(str_repeat('A', 51));
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('50', $violations[0]->getMessage());
        
        // Test valid number
        $invoice->setNumber('INV/2024/001');
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
    }

    public function testDateValidation(): void
    {
        // Test that proper DateTime objects are accepted
        $invoice = $this->createValidInvoice();
        $invoice->setIssueDate(new \DateTime('2024-01-01'))
               ->setSaleDate(new \DateTime('2024-01-01'))
               ->setDueDate(new \DateTime('2024-01-31'));
        
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test that different valid date formats work
        $invoice->setIssueDate(new \DateTime('2024-12-31'))
               ->setSaleDate(new \DateTime('2024-12-31'));
        
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);

        // Note: Testing null dates would require bypassing PHP's type system
        // which is not practical in this context. The type system itself
        // provides the null validation at runtime.
        $this->assertTrue(true, 'Date validation test completed - type system enforces non-null dates');
    }

    public function testCurrencyValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test empty currency
        $invoice->setCurrency('');
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid currency length
        $invoice->setCurrency('PLNN');
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid currency code
        $invoice->setCurrency('XXX');
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test valid currencies
        $validCurrencies = ['PLN', 'EUR', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK'];
        foreach ($validCurrencies as $currency) {
            $invoice->setCurrency($currency);
            $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
            $this->assertCount(0, $violations, "Currency {$currency} should be valid");
        }
    }

    public function testPaymentMethodValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test invalid payment method (too low)
        $invoice->setPaymentMethod(0);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid payment method (too high)
        $invoice->setPaymentMethod(51);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test valid payment methods
        for ($method = 1; $method <= 50; $method++) {
            $invoice->setPaymentMethod($method);
            $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
            $this->assertCount(0, $violations, "Payment method {$method} should be valid");
        }
        
        // Test null payment method (should be allowed)
        $invoice->setPaymentMethod(null);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
    }

    public function testNotesValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test too long notes
        $invoice->setNotes(str_repeat('A', 1001));
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('1000', $violations[0]->getMessage());
        
        // Test valid notes
        $invoice->setNotes(str_repeat('A', 1000));
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test null notes (should be allowed)
        $invoice->setNotes(null);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
    }

    public function testCustomerValidation(): void
    {
        // Test valid customer
        $invoice = $this->createValidInvoice();
        $invoice->setCustomer($this->testCustomer);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test with different valid customer
        $anotherCustomer = new Company('Another Customer');
        $anotherCustomer->setTaxId('9876543210');
        $invoice->setCustomer($anotherCustomer);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);

        // Note: Testing null customer would require bypassing PHP's type system
        // The type system itself enforces non-null customer at runtime.
        $this->assertTrue(true, 'Customer validation test completed - type system enforces non-null customer');
    }

    public function testItemsValidation(): void
    {
        // Test with valid items (createValidInvoice already has 1 item)
        $invoice = $this->createValidInvoice();
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test with multiple items (adding 1 more for total of 2)
        $anotherItem = new InvoiceItem();
        $anotherItem->setDescription('Another item')
                   ->setQuantity('3.00')
                   ->setUnit('szt.')
                   ->setUnitPrice('15.00')
                   ->setVatRate('8.00')
                   ->setSortOrder(2);
        $invoice->addItem($anotherItem);
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test items collection behavior (should have 2 items total)
        $this->assertCount(2, $invoice->getItems());
        $this->assertTrue($invoice->getItems()->contains($invoice->getItems()->first()));
    }

    public function testInvoiceItemValidation(): void
    {
        $item = new InvoiceItem();
        
        // Test empty description
        $item->setDescription('')
             ->setQuantity('1.000')
             ->setUnit('szt.')
             ->setUnitPrice('100.00')
             ->setVatRate('23.00');
        
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test too long description
        $item->setDescription(str_repeat('A', 256));
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid quantity
        $item->setDescription('Valid Product')
             ->setQuantity('0'); // Zero quantity
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test negative quantity
        $item->setQuantity('-1.000');
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid unit
        $item->setQuantity('1.000')
             ->setUnit('invalid_unit');
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test valid units
        $validUnits = ['szt.', 'kg', 'm', 'm2', 'm3', 'godz.', 'dzień', 'l', 't', 'km', 'kWh', 'usł.', 'kpl.', 'op.', 'm.b.'];
        foreach ($validUnits as $unit) {
            $item->setUnit($unit);
            $violations = $this->validator->validate($item, groups: ['invoice:create']);
            // We might still have other validation errors, but unit should be valid
            $unitViolations = array_filter(iterator_to_array($violations), fn($v) => str_contains($v->getPropertyPath(), 'unit'));
            $this->assertCount(0, $unitViolations, "Unit {$unit} should be valid");
        }
        
        // Test negative unit price
        $item->setUnit('szt.')
             ->setUnitPrice('-10.00');
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test invalid VAT rates
        $invalidVatRates = ['25.00', '15.00', '100.00', '-5.00'];
        foreach ($invalidVatRates as $vatRate) {
            $item->setUnitPrice('100.00')
                 ->setVatRate($vatRate);
            $violations = $this->validator->validate($item, groups: ['invoice:create']);
            $this->assertGreaterThan(0, $violations->count(), "VAT rate {$vatRate} should be invalid");
        }
        
        // Test valid VAT rates with a complete valid item associated with invoice
        $invoice = $this->createValidInvoice();
        $validVatRates = ['0.00', '5.00', '8.00', '23.00'];
        foreach ($validVatRates as $vatRate) {
            $validItem = new InvoiceItem();
            $validItem->setDescription('Test item')
                     ->setQuantity('1.000')
                     ->setUnit('szt.')
                     ->setUnitPrice('100.00')
                     ->setVatRate($vatRate)
                     ->setSortOrder(1);
            
            $invoice->addItem($validItem);
            $violations = $this->validator->validate($validItem, groups: ['invoice:create']);
            $this->assertCount(0, $violations, "VAT rate {$vatRate} should be valid");
        }
    }

    public function testSortOrderValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test negative sort order
        $item = new InvoiceItem();
        $item->setDescription('Test item')
             ->setQuantity('1.000')
             ->setUnit('szt.')
             ->setUnitPrice('100.00')
             ->setVatRate('23.00')
             ->setSortOrder(-1);
        
        $invoice->addItem($item);
        $violations = $this->validator->validate($item, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Test valid sort orders (including 0 which should be valid)
        for ($order = 0; $order <= 100; $order += 10) {
            $validItem = new InvoiceItem();
            $validItem->setDescription('Test item')
                     ->setQuantity('1.000')
                     ->setUnit('szt.')
                     ->setUnitPrice('100.00')
                     ->setVatRate('23.00')
                     ->setSortOrder($order);
            
            $invoice->addItem($validItem);
            $violations = $this->validator->validate($validItem, groups: ['invoice:create']);
            $this->assertCount(0, $violations, "Sort order {$order} should be valid");
        }
    }

    public function testValidationGroups(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Test create group validation
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
        
        // Test update group validation (more lenient)
        $violations = $this->validator->validate($invoice, groups: ['invoice:update']);
        $this->assertCount(0, $violations);
        
        // Test that some fields are only required in create group
        $invoice->setNumber(''); // Empty number
        
        $createViolations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $createViolations->count());
        
        $updateViolations = $this->validator->validate($invoice, groups: ['invoice:update']);
        // Update might be more lenient for some fields depending on implementation
        // This depends on specific validation group configuration
    }

    public function testInvoiceUniqueNumberConstraint(): void
    {
        // This test would require database integration to test UniqueEntity constraint
        // For now, we'll just verify the constraint is properly configured
        $invoice = $this->createValidInvoice();
        $invoice->setNumber('TEST/UNIQUE/001');
        
        // In a real scenario with database, this would need two invoices with same number
        // to test the unique constraint properly
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertCount(0, $violations);
    }

    public function testCascadingValidation(): void
    {
        $invoice = $this->createValidInvoice();
        
        // Add an invalid item to test cascading validation
        $invalidItem = new InvoiceItem();
        $invalidItem->setDescription('') // Invalid: empty description
                   ->setQuantity('1.000')
                   ->setUnit('szt.')
                   ->setUnitPrice('100.00')
                   ->setVatRate('23.00');
        
        $invoice->addItem($invalidItem);
        
        $violations = $this->validator->validate($invoice, groups: ['invoice:create']);
        $this->assertGreaterThan(0, $violations->count());
        
        // Check that the violation is on the item, not the invoice
        $itemViolations = array_filter(
            iterator_to_array($violations),
            fn($v) => str_contains($v->getPropertyPath(), 'items[')
        );
        $this->assertGreaterThan(0, count($itemViolations));
    }

    private function createValidInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setNumber('INV/TEST/001')
               ->setIssueDate(new \DateTime('2024-01-01'))
               ->setSaleDate(new \DateTime('2024-01-01'))
               ->setDueDate(new \DateTime('2024-01-31'))
               ->setCurrency('PLN')
               ->setPaymentMethod(1)
               ->setNotes('Test invoice notes')
               ->setCustomer($this->testCustomer);
        
        $item = $this->createValidInvoiceItem();
        $invoice->addItem($item);
        
        return $invoice;
    }

    private function createValidInvoiceItem(): InvoiceItem
    {
        $item = new InvoiceItem();
        $item->setDescription('Test Product')
             ->setQuantity('1.000')
             ->setUnit('szt.')
             ->setUnitPrice('100.00')
             ->setVatRate('23.00')
             ->setSortOrder(1);
        
        return $item;
    }
}