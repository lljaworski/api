<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentMethodEnum: string
{
    case DIGITAL_WALLETS = 'digital_wallets';
    case CASH = 'cash';
    case WIRE_TRANSFERS = 'wire_transfers';
    case AUTOMATIC_PAYMENTS = 'automatic_payments';

    public function getLabel(): string
    {
        return match ($this) {
            self::DIGITAL_WALLETS => 'Digital Wallets',
            self::CASH => 'Cash',
            self::WIRE_TRANSFERS => 'Wire Transfers',
            self::AUTOMATIC_PAYMENTS => 'Automatic Payments',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DIGITAL_WALLETS => 'PayPal, Apple Pay, Google Pay, etc.',
            self::CASH => 'Physical cash payments',
            self::WIRE_TRANSFERS => 'Bank transfers, SEPA, ACH',
            self::AUTOMATIC_PAYMENTS => 'Direct debits, recurring payments',
        };
    }
}
