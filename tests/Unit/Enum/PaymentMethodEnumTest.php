<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\PaymentMethodEnum;
use PHPUnit\Framework\TestCase;

class PaymentMethodEnumTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('digital_wallets', PaymentMethodEnum::DIGITAL_WALLETS->value);
        $this->assertEquals('cash', PaymentMethodEnum::CASH->value);
        $this->assertEquals('wire_transfers', PaymentMethodEnum::WIRE_TRANSFERS->value);
        $this->assertEquals('automatic_payments', PaymentMethodEnum::AUTOMATIC_PAYMENTS->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('Digital Wallets', PaymentMethodEnum::DIGITAL_WALLETS->getLabel());
        $this->assertEquals('Cash', PaymentMethodEnum::CASH->getLabel());
        $this->assertEquals('Wire Transfers', PaymentMethodEnum::WIRE_TRANSFERS->getLabel());
        $this->assertEquals('Automatic Payments', PaymentMethodEnum::AUTOMATIC_PAYMENTS->getLabel());
    }

    public function testDescriptions(): void
    {
        $this->assertEquals(
            'PayPal, Apple Pay, Google Pay, etc.',
            PaymentMethodEnum::DIGITAL_WALLETS->getDescription()
        );
        $this->assertEquals(
            'Physical cash payments',
            PaymentMethodEnum::CASH->getDescription()
        );
        $this->assertEquals(
            'Bank transfers, SEPA, ACH',
            PaymentMethodEnum::WIRE_TRANSFERS->getDescription()
        );
        $this->assertEquals(
            'Direct debits, recurring payments',
            PaymentMethodEnum::AUTOMATIC_PAYMENTS->getDescription()
        );
    }

    public function testAllEnumCasesAreCovered(): void
    {
        $allCases = PaymentMethodEnum::cases();
        $this->assertCount(4, $allCases);
        
        $expectedValues = ['digital_wallets', 'cash', 'wire_transfers', 'automatic_payments'];
        $actualValues = array_map(fn($case) => $case->value, $allCases);
        
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testFromString(): void
    {
        $this->assertEquals(
            PaymentMethodEnum::DIGITAL_WALLETS,
            PaymentMethodEnum::from('digital_wallets')
        );
        $this->assertEquals(
            PaymentMethodEnum::CASH,
            PaymentMethodEnum::from('cash')
        );
        $this->assertEquals(
            PaymentMethodEnum::WIRE_TRANSFERS,
            PaymentMethodEnum::from('wire_transfers')
        );
        $this->assertEquals(
            PaymentMethodEnum::AUTOMATIC_PAYMENTS,
            PaymentMethodEnum::from('automatic_payments')
        );
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\ValueError::class);
        PaymentMethodEnum::from('invalid');
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(
            PaymentMethodEnum::DIGITAL_WALLETS,
            PaymentMethodEnum::tryFrom('digital_wallets')
        );
        $this->assertEquals(
            PaymentMethodEnum::CASH,
            PaymentMethodEnum::tryFrom('cash')
        );
        $this->assertEquals(
            PaymentMethodEnum::WIRE_TRANSFERS,
            PaymentMethodEnum::tryFrom('wire_transfers')
        );
        $this->assertEquals(
            PaymentMethodEnum::AUTOMATIC_PAYMENTS,
            PaymentMethodEnum::tryFrom('automatic_payments')
        );
        $this->assertNull(PaymentMethodEnum::tryFrom('invalid'));
    }

    public function testEnumCaseConsistency(): void
    {
        // Ensure all cases have labels and descriptions
        foreach (PaymentMethodEnum::cases() as $case) {
            $this->assertNotEmpty($case->getLabel(), "Label for {$case->value} should not be empty");
            $this->assertNotEmpty($case->getDescription(), "Description for {$case->value} should not be empty");
        }
    }

    public function testEnumValuesAreUnique(): void
    {
        $values = array_map(fn($case) => $case->value, PaymentMethodEnum::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(
            count($values),
            $uniqueValues,
            'All enum values should be unique'
        );
    }

    public function testEnumLabelsAreUnique(): void
    {
        $labels = array_map(fn($case) => $case->getLabel(), PaymentMethodEnum::cases());
        $uniqueLabels = array_unique($labels);
        
        $this->assertCount(
            count($labels),
            $uniqueLabels,
            'All enum labels should be unique'
        );
    }
}
