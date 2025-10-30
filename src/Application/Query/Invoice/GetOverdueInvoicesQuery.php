<?php

declare(strict_types=1);

namespace App\Application\Query\Invoice;

use App\Application\Query\AbstractQuery;

/**
 * Query to get overdue invoices (issued, unpaid, past due date).
 */
final class GetOverdueInvoicesQuery extends AbstractQuery
{
    public function __construct()
    {
        parent::__construct();
    }
}