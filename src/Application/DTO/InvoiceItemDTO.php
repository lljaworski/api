<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for InvoiceItem data.
 */
final class InvoiceItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $description,
        public readonly string $quantity,
        public readonly string $unit,
        public readonly string $unitPrice,
        public readonly string $netAmount,
        public readonly string $vatRate,
        public readonly string $vatAmount,
        public readonly string $grossAmount,
        public readonly int $sortOrder
    ) {
    }

    public function getFormattedUnitPrice(string $currency = 'PLN'): string
    {
        return number_format((float) $this->unitPrice, 2, ',', ' ') . ' ' . $currency;
    }

    public function getFormattedNetAmount(string $currency = 'PLN'): string
    {
        return number_format((float) $this->netAmount, 2, ',', ' ') . ' ' . $currency;
    }

    public function getFormattedVatAmount(string $currency = 'PLN'): string
    {
        return number_format((float) $this->vatAmount, 2, ',', ' ') . ' ' . $currency;
    }

    public function getFormattedGrossAmount(string $currency = 'PLN'): string
    {
        return number_format((float) $this->grossAmount, 2, ',', ' ') . ' ' . $currency;
    }

    public function getVatRatePercentage(): string
    {
        return $this->vatRate . '%';
    }

    public function getDisplayString(): string
    {
        return sprintf(
            '%s (%s %s × %s PLN, VAT %s%%)', 
            $this->description,
            $this->quantity,
            $this->unit,
            $this->unitPrice,
            $this->vatRate
        );
    }

    public function getQuantityWithUnit(): string
    {
        return $this->quantity . ' ' . $this->unit;
    }

    /**
     * Returns data formatted for KSeF fields
     */
    public function getKsefData(): array
    {
        return [
            'P_7' => $this->description,      // Description
            'P_8A' => $this->unit,           // Unit
            'P_8B' => $this->quantity,       // Quantity
            'P_9A' => $this->unitPrice,      // Unit price
            'P_11' => $this->netAmount,      // Net amount
            'P_12' => (int) $this->vatRate   // VAT rate as integer
        ];
    }
}