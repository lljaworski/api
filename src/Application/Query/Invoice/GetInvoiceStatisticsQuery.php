<?php

declare(strict_types=1);

namespace App\Application\Query\Invoice;

use App\Application\Query\AbstractQuery;

/**
 * Query to get invoice statistics for dashboard.
 */
final class GetInvoiceStatisticsQuery extends AbstractQuery
{
    public function __construct()
    {
        parent::__construct();
    }
}