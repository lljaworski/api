<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;

class VatCalculationService
{
    private const SCALE = 2;
    private const VAT_RATES = ['0.00', '5.00', '8.00', '23.00'];

    /**
     * Calculate totals for a single invoice item
     */
    public function calculateItemTotals(
        string $quantity,
        string $unitPrice,
        string $vatRate
    ): array {
        // Validate VAT rate
        if (!in_array($vatRate, self::VAT_RATES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid VAT rate: %s. Allowed rates: %s', $vatRate, implode(', ', self::VAT_RATES))
            );
        }

        // Calculate net amount (quantity * unit price)
        $netAmount = bcmul($quantity, $unitPrice, self::SCALE);

        // Calculate VAT amount (net amount * VAT rate / 100)
        $vatDecimal = bcdiv($vatRate, '100', 4);
        $vatAmount = bcmul($netAmount, $vatDecimal, self::SCALE);

        // Calculate gross amount (net amount + VAT amount)
        $grossAmount = bcadd($netAmount, $vatAmount, self::SCALE);

        return [
            'netAmount' => $netAmount,
            'vatAmount' => $vatAmount,
            'grossAmount' => $grossAmount
        ];
    }

    /**
     * Calculate and update totals for an invoice item
     */
    public function recalculateInvoiceItem(InvoiceItem $item): InvoiceItem
    {
        if (!$item->getQuantity() || !$item->getUnitPrice() || !$item->getVatRate()) {
            return $item;
        }

        $totals = $this->calculateItemTotals(
            $item->getQuantity(),
            $item->getUnitPrice(),
            $item->getVatRate()
        );

        $item->setNetAmount($totals['netAmount']);
        $item->setVatAmount($totals['vatAmount']);
        $item->setGrossAmount($totals['grossAmount']);

        return $item;
    }

    /**
     * Calculate invoice totals from all items
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        $totals = [
            'subtotal' => '0.00',
            'vatAmount' => '0.00',
            'total' => '0.00',
            'vatBreakdown' => []
        ];

        $vatBreakdown = [];

        foreach ($invoice->getItems() as $item) {
            // Add to overall totals
            $totals['subtotal'] = bcadd($totals['subtotal'], $item->getNetAmount(), self::SCALE);
            $totals['vatAmount'] = bcadd($totals['vatAmount'], $item->getVatAmount(), self::SCALE);

            // Group by VAT rate for breakdown
            $vatRate = $item->getVatRate();
            if (!isset($vatBreakdown[$vatRate])) {
                $vatBreakdown[$vatRate] = [
                    'rate' => $vatRate,
                    'netAmount' => '0.00',
                    'vatAmount' => '0.00',
                    'grossAmount' => '0.00'
                ];
            }

            $vatBreakdown[$vatRate]['netAmount'] = bcadd(
                $vatBreakdown[$vatRate]['netAmount'],
                $item->getNetAmount(),
                self::SCALE
            );
            $vatBreakdown[$vatRate]['vatAmount'] = bcadd(
                $vatBreakdown[$vatRate]['vatAmount'],
                $item->getVatAmount(),
                self::SCALE
            );
            $vatBreakdown[$vatRate]['grossAmount'] = bcadd(
                $vatBreakdown[$vatRate]['grossAmount'],
                $item->getGrossAmount(),
                self::SCALE
            );
        }

        $totals['total'] = bcadd($totals['subtotal'], $totals['vatAmount'], self::SCALE);
        $totals['vatBreakdown'] = array_values($vatBreakdown);

        // Sort VAT breakdown by rate (descending)
        usort($totals['vatBreakdown'], function ($a, $b) {
            return bccomp($b['rate'], $a['rate'], 2);
        });

        return $totals;
    }

    /**
     * Update invoice totals based on its items
     */
    public function recalculateInvoiceTotals(Invoice $invoice): Invoice
    {
        $totals = $this->calculateInvoiceTotals($invoice);

        $invoice->setSubtotal($totals['subtotal']);
        $invoice->setVatAmount($totals['vatAmount']);
        $invoice->setTotal($totals['total']);

        return $invoice;
    }

    /**
     * Get KSeF-compliant totals for invoice
     * Returns data formatted for KSeF XML generation
     */
    public function getKsefTotals(Invoice $invoice): array
    {
        $totals = $this->calculateInvoiceTotals($invoice);
        $ksefTotals = [];

        // Find totals for standard VAT rates
        foreach ($totals['vatBreakdown'] as $breakdown) {
            $rate = $breakdown['rate'];
            
            switch ($rate) {
                case '23.00':
                    $ksefTotals['P_13_1'] = $breakdown['netAmount']; // Net amount for 23% VAT
                    $ksefTotals['P_14_1'] = $breakdown['vatAmount']; // VAT amount for 23%
                    break;
                case '8.00':
                    $ksefTotals['P_13_2'] = $breakdown['netAmount']; // Net amount for 8% VAT
                    $ksefTotals['P_14_2'] = $breakdown['vatAmount']; // VAT amount for 8%
                    break;
                case '5.00':
                    $ksefTotals['P_13_3'] = $breakdown['netAmount']; // Net amount for 5% VAT
                    $ksefTotals['P_14_3'] = $breakdown['vatAmount']; // VAT amount for 5%
                    break;
                case '0.00':
                    $ksefTotals['P_13_4'] = $breakdown['netAmount']; // Net amount for 0% VAT
                    break;
            }
        }

        // Total invoice amount (P_15 in KSeF)
        $ksefTotals['P_15'] = $totals['total'];

        return $ksefTotals;
    }

    /**
     * Validate VAT rate
     */
    public function isValidVatRate(string $vatRate): bool
    {
        return in_array($vatRate, self::VAT_RATES, true);
    }

    /**
     * Get all available VAT rates
     */
    public function getAvailableVatRates(): array
    {
        return self::VAT_RATES;
    }

    /**
     * Format amount for display (with currency)
     */
    public function formatAmount(string $amount, string $currency = 'PLN'): string
    {
        return number_format((float) $amount, 2, ',', ' ') . ' ' . $currency;
    }

    /**
     * Convert percentage to decimal for calculations
     */
    public function percentageToDecimal(string $percentage): string
    {
        return bcdiv($percentage, '100', 4);
    }

    /**
     * Convert decimal to percentage for display
     */
    public function decimalToPercentage(string $decimal): string
    {
        return bcmul($decimal, '100', 2);
    }

    /**
     * Round amount to currency precision
     */
    public function roundAmount(string $amount): string
    {
        return bcadd($amount, '0', self::SCALE);
    }

    /**
     * Calculate discount amount and update totals
     */
    public function applyDiscount(
        string $originalAmount,
        string $discountPercentage
    ): array {
        $discountDecimal = $this->percentageToDecimal($discountPercentage);
        $discountAmount = bcmul($originalAmount, $discountDecimal, self::SCALE);
        $finalAmount = bcsub($originalAmount, $discountAmount, self::SCALE);

        return [
            'originalAmount' => $originalAmount,
            'discountPercentage' => $discountPercentage,
            'discountAmount' => $discountAmount,
            'finalAmount' => $finalAmount
        ];
    }

    /**
     * Validate invoice totals for consistency
     */
    public function validateInvoiceTotals(Invoice $invoice): array
    {
        $calculatedTotals = $this->calculateInvoiceTotals($invoice);
        $errors = [];

        // Check if stored totals match calculated totals
        if (bccomp($invoice->getSubtotal(), $calculatedTotals['subtotal'], self::SCALE) !== 0) {
            $errors[] = sprintf(
                'Subtotal mismatch: stored %s, calculated %s',
                $invoice->getSubtotal(),
                $calculatedTotals['subtotal']
            );
        }

        if (bccomp($invoice->getVatAmount(), $calculatedTotals['vatAmount'], self::SCALE) !== 0) {
            $errors[] = sprintf(
                'VAT amount mismatch: stored %s, calculated %s',
                $invoice->getVatAmount(),
                $calculatedTotals['vatAmount']
            );
        }

        if (bccomp($invoice->getTotal(), $calculatedTotals['total'], self::SCALE) !== 0) {
            $errors[] = sprintf(
                'Total mismatch: stored %s, calculated %s',
                $invoice->getTotal(),
                $calculatedTotals['total']
            );
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'calculatedTotals' => $calculatedTotals
        ];
    }
}