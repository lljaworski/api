<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\InvoiceNextNumberProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/invoices/next-number',
            provider: InvoiceNextNumberProvider::class,
            security: "is_granted('ROLE_B2B')",
            normalizationContext: ['groups' => ['invoice_next_number:read']],
            description: 'Returns the next available invoice number based on the configured format and the provided issue date. Optional query parameter: date (YYYY-MM-DD format, defaults to today)',
        ),
    ],
    shortName: 'InvoiceNextNumber',
    description: 'Get the next available invoice number for a given date'
)]
class InvoiceNextNumber
{
    #[Groups(['invoice_next_number:read'])]
    public string $nextNumber;

    #[Groups(['invoice_next_number:read'])]
    public string $format;

    #[Groups(['invoice_next_number:read'])]
    public string $issueDate;

    #[Groups(['invoice_next_number:read'])]
    public int $sequenceNumber;

    public function __construct(
        string $nextNumber,
        string $format,
        string $issueDate,
        int $sequenceNumber
    ) {
        $this->nextNumber = $nextNumber;
        $this->format = $format;
        $this->issueDate = $issueDate;
        $this->sequenceNumber = $sequenceNumber;
    }
}
