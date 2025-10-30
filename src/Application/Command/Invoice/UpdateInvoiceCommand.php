<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to update an existing invoice in the system.
 */
final class UpdateInvoiceCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly int $id,
        
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly ?int $customerId = null,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $issueDate = null,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $saleDate = null,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $dueDate = null,
        
        #[Assert\Length(exactly: 3)]
        #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK'])]
        public readonly ?string $currency = null,
        
        #[Assert\Range(min: 1, max: 50)]
        public readonly ?int $paymentMethod = null,
        
        #[Assert\Length(max: 1000)]
        public readonly ?string $notes = null,
        
        #[Assert\Type('array')]
        #[Assert\Valid]
        public readonly ?array $items = null // Array of UpdateInvoiceItemCommand or null to keep existing
    ) {
        parent::__construct();
    }
}