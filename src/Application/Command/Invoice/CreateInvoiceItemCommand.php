<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to create a new invoice item.
 */
final class CreateInvoiceItemCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $description,
        
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,3})?$/')]
        public readonly string $quantity,
        
        #[Assert\NotBlank]
        #[Assert\Length(max: 10)]
        #[Assert\Choice(choices: ['szt.', 'kg', 'm', 'm2', 'm3', 'godz.', 'dzień', 'l', 't', 'km', 'kWh', 'usł.', 'kpl.', 'op.', 'm.b.'])]
        public readonly string $unit,
        
        #[Assert\NotBlank]
        #[Assert\PositiveOrZero]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        public readonly string $unitPrice,
        
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['0.00', '5.00', '8.00', '23.00'])]
        public readonly string $vatRate,
        
        #[Assert\PositiveOrZero]
        public readonly int $sortOrder = 0
    ) {
    }
}