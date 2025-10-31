<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\DTO\InvoiceStatisticsDTO;
use App\Application\Query\Invoice\GetInvoiceStatisticsQuery;
use App\Repository\InvoiceRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetInvoiceStatisticsQuery to retrieve invoice statistics for dashboard.
 */
final class GetInvoiceStatisticsQueryHandler
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetInvoiceStatisticsQuery $query): InvoiceStatisticsDTO
    {
        $stats = $this->invoiceRepository->getInvoiceStatistics();
        $monthlyStats = $this->invoiceRepository->getMonthlyInvoiceStatistics();
        
        return new InvoiceStatisticsDTO(
            totalInvoices: $stats['total_count'] ?? 0,
            paidInvoices: $stats['paid_count'] ?? 0,
            unpaidInvoices: $stats['unpaid_count'] ?? 0,
            overdueInvoices: $stats['overdue_count'] ?? 0,
            draftInvoices: $stats['draft_count'] ?? 0,
            totalAmount: $stats['total_amount'] ?? '0.00',
            paidAmount: $stats['paid_amount'] ?? '0.00',
            unpaidAmount: $stats['unpaid_amount'] ?? '0.00',
            overdueAmount: $stats['overdue_amount'] ?? '0.00',
            monthlyStats: $monthlyStats
        );
    }
}