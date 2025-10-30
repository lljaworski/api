<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to mark an invoice as paid.
 */
final class PayInvoiceCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly int $id,
        
        #[Assert\Type(\DateTimeInterface::class)]
        public readonly ?\DateTimeInterface $paidAt = null // null = now
    ) {
        parent::__construct();
    }
}