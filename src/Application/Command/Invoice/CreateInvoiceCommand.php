<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use App\Application\Command\AbstractCommand;
use App\Enum\PaymentMethodEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to create a new invoice in the system.
 */
final class CreateInvoiceCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly int $customerId,
        
        #[Assert\NotNull]
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly \DateTimeInterface $issueDate,
        
        #[Assert\NotNull]
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly \DateTimeInterface $saleDate,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $dueDate = null,
        
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 3)]
        #[Assert\Choice(choices: ['PLN', 'EUR', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'NOK', 'DKK'])]
        public readonly string $currency = 'PLN',
        
        public readonly ?PaymentMethodEnum $paymentMethod = null,
        
        #[Assert\Length(max: 1000)]
        public readonly ?string $notes = null,
        
        #[Assert\NotNull]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public readonly array $items = [] // Array of CreateInvoiceItemCommand
    ) {
        parent::__construct();
    }
}