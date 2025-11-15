<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\Query\Invoice\GetNextInvoiceNumberQuery;
use App\Service\InvoiceNumberGenerator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetNextInvoiceNumberQueryHandler
{
    public function __construct(
        private InvoiceNumberGenerator $invoiceNumberGenerator,
    ) {}

    public function __invoke(GetNextInvoiceNumberQuery $query): string
    {
        return $this->invoiceNumberGenerator->previewNextNumber($query->issueDate);
    }
}