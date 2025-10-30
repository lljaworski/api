<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for paginated collection of invoices.
 */
final class InvoiceCollectionDTO
{
    /**
     * @param InvoiceDTO[] $invoices
     */
    public function __construct(
        public readonly array $invoices,
        public readonly int $total,
        public readonly int $page = 1,
        public readonly int $itemsPerPage = 30
    ) {
    }
    
    public function hasNextPage(): bool
    {
        return ($this->page * $this->itemsPerPage) < $this->total;
    }
    
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->itemsPerPage);
    }

    public function isEmpty(): bool
    {
        return empty($this->invoices);
    }

    public function count(): int
    {
        return count($this->invoices);
    }

    /**
     * Get summary statistics for the current page
     */
    public function getPageSummary(): array
    {
        if ($this->isEmpty()) {
            return [
                'totalAmount' => '0.00',
                'paidAmount' => '0.00',
                'unpaidAmount' => '0.00',
                'overdueCount' => 0
            ];
        }

        $totalAmount = '0.00';
        $paidAmount = '0.00';
        $unpaidAmount = '0.00';
        $overdueCount = 0;

        foreach ($this->invoices as $invoice) {
            $totalAmount = bcadd($totalAmount, $invoice->total, 2);
            
            if ($invoice->isPaid) {
                $paidAmount = bcadd($paidAmount, $invoice->total, 2);
            } else {
                $unpaidAmount = bcadd($unpaidAmount, $invoice->total, 2);
                
                if ($invoice->isOverdue()) {
                    $overdueCount++;
                }
            }
        }

        return [
            'totalAmount' => $totalAmount,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'overdueCount' => $overdueCount
        ];
    }
}