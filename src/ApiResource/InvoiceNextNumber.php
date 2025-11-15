<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\InvoiceNextNumberProvider;

#[ApiResource(
    shortName: 'InvoiceNextNumber',
    operations: [
        new Get(
            uriTemplate: '/invoices/next-number{._format}',
            security: "is_granted('ROLE_B2B')",
            securityMessage: 'Access denied. B2B role required.',
            provider: InvoiceNextNumberProvider::class,
        )
    ],
    formats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
    paginationEnabled: false,
)]
class InvoiceNextNumber
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly string $issueDate,
        public readonly string $format,
    ) {}
}