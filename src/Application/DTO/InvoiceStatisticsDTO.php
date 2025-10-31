<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * DTO for invoice statistics dashboard data.
 */
final readonly class InvoiceStatisticsDTO
{
    public function __construct(
        public int $totalInvoices,
        public int $paidInvoices,
        public int $unpaidInvoices,
        public int $overdueInvoices,
        public int $draftInvoices,
        public string $totalAmount,
        public string $paidAmount,
        public string $unpaidAmount,
        public string $overdueAmount,
        public array $monthlyStats = [] // Array of ['month' => 'YYYY-MM', 'count' => int, 'amount' => string]
    ) {
    }
}