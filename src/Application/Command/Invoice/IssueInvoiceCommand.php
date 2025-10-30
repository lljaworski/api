<?php

declare(strict_types=1);

namespace App\Application\Command\Invoice;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to issue an invoice (change status from draft to issued).
 */
final class IssueInvoiceCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly int $id
    ) {
        parent::__construct();
    }
}