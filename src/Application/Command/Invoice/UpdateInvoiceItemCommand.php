<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to update an invoice item.
 */
final class UpdateInvoiceItemCommand
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly ?int $id = null, // null for new items
        
        #[Assert\Length(max: 255)]
        public readonly ?string $description = null,
        
        #[Assert\Positive]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,3})?$/')]
        public readonly ?string $quantity = null,
        
        #[Assert\Length(max: 10)]
        #[Assert\Choice(choices: ['szt.', 'kg', 'm', 'm2', 'm3', 'godz.', 'dzień', 'l', 't', 'km', 'kWh', 'usł.', 'kpl.', 'op.', 'm.b.'])]
        public readonly ?string $unit = null,
        
        #[Assert\PositiveOrZero]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        public readonly ?string $unitPrice = null,
        
        #[Assert\Choice(choices: ['0.00', '5.00', '8.00', '23.00'])]
        public readonly ?string $vatRate = null,
        
        #[Assert\PositiveOrZero]
        public readonly ?int $sortOrder = null,
        
        public readonly bool $markForDeletion = false // For removing items during update
    ) {
    }
}