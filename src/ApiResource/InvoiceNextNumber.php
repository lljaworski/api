<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\State\InvoiceNextNumberProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'InvoiceNextNumber',
    operations: [
        new Get(
            uriTemplate: '/invoices/next-number{._format}',
            security: "is_granted('ROLE_B2B')",
            securityMessage: 'Access denied. B2B role required.',
            provider: InvoiceNextNumberProvider::class,
            parameters: [
                'date' => new QueryParameter(
                    constraints: [
                        new Assert\NotBlank(message: 'Date parameter is required'),
                        new Assert\Date(message: 'Invalid date format. Use YYYY-MM-DD')
                    ],
                    description: 'Issue date for invoice number generation (format: YYYY-MM-DD)',
                    required: true
                )
            ]
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