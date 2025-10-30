<?php

declare(strict_types=1);

namespace App\Application\Query\Invoice;

use App\Application\Query\AbstractQuery;
use App\Enum\InvoiceStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get a collection of invoices with pagination and filtering.
 */
final class GetInvoicesQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $itemsPerPage = 30,
        
        public readonly ?string $search = null,
        
        public readonly ?InvoiceStatus $status = null,
        
        public readonly ?bool $isPaid = null,
        
        #[Assert\Positive]
        public readonly ?int $customerId = null,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $issueDateFrom = null,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $issueDateTo = null
    ) {
        parent::__construct();
    }
}