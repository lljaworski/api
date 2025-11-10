<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Enum\InvoiceStatus;
use App\Enum\PaymentMethodEnum;

/**
 * Data Transfer Object for Invoice data.
 */
final class InvoiceDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $number,
        public readonly \DateTimeImmutable $issueDate,
        public readonly \DateTimeImmutable $saleDate,
        public readonly ?\DateTimeImmutable $dueDate,
        public readonly string $currency,
        public readonly ?PaymentMethodEnum $paymentMethod,
        public readonly InvoiceStatus $status,
        public readonly bool $isPaid,
        public readonly ?\DateTimeImmutable $paidAt,
        public readonly ?string $notes,
        public readonly ?string $ksefNumber,
        public readonly ?\DateTimeImmutable $ksefSubmittedAt,
        public readonly string $subtotal,
        public readonly string $vatAmount,
        public readonly string $total,
        public readonly CompanyDTO $customer,
        public readonly array $items, // Array of InvoiceItemDTO
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null
    ) {
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable() && $this->isActive();
    }

    public function isDeletable(): bool
    {
        return $this->status->isDeletable() && $this->isActive();
    }

    public function isOverdue(): bool
    {
        if ($this->status !== InvoiceStatus::ISSUED || $this->isPaid || $this->dueDate === null) {
            return false;
        }
        
        return $this->dueDate < new \DateTimeImmutable('today');
    }

    public function getFormattedTotal(?string $currency = null): string
    {
        $curr = $currency ?? $this->currency;
        return number_format((float) $this->total, 2, ',', ' ') . ' ' . $curr;
    }

    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    public function getDaysUntilDue(): ?int
    {
        if ($this->dueDate === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $interval = $today->diff($this->dueDate);
        
        return $interval->invert ? -$interval->days : $interval->days;
    }

    public function getItemsCount(): int
    {
        return count($this->items);
    }

    public function hasItems(): bool
    {
        return !empty($this->items);
    }
}