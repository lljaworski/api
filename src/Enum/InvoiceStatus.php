<?php

declare(strict_types=1);

namespace App\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::DRAFT => in_array($status, [self::ISSUED, self::CANCELLED]),
            self::ISSUED => in_array($status, [self::PAID, self::CANCELLED]),
            self::PAID => false, // Paid invoices cannot be changed
            self::CANCELLED => false, // Cancelled invoices cannot be changed
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function isDeletable(): bool
    {
        return in_array($this, [self::DRAFT, self::CANCELLED]);
    }
}