<?php

declare(strict_types=1);

namespace App\Application\Query\Invoice;

use DateTimeImmutable;

readonly class GetNextInvoiceNumberQuery
{
    public function __construct(
        public DateTimeImmutable $issueDate,
    ) {}
}