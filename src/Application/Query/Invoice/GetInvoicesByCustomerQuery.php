<?php

declare(strict_types=1);

namespace App\Application\Query\Invoice;

use App\Application\Query\AbstractQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get invoices for a specific customer.
 */
final class GetInvoicesByCustomerQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $customerId,
        
        #[Assert\Positive]
        public readonly int $page = 1,
        
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $itemsPerPage = 30
    ) {
        parent::__construct();
    }
}