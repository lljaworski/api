<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\Command\Invoice\PayInvoiceCommand;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles PayInvoiceCommand to mark an invoice as paid.
 */
final class PayInvoiceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepository $invoiceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(PayInvoiceCommand $command): mixed
    {
        $invoice = $this->invoiceRepository->find($command->id);
        if (!$invoice || $invoice->isDeleted()) {
            throw new NotFoundHttpException('Invoice not found');
        }

        if ($invoice->isPaid()) {
            throw new \InvalidArgumentException('Invoice is already paid');
        }

        // Business rule: Only issued invoices can be marked as paid
        if ($invoice->getStatus() !== \App\Enum\InvoiceStatus::ISSUED) {
            throw new \InvalidArgumentException(
                sprintf('Cannot mark invoice with status %s as paid. Invoice must be issued first.', $invoice->getStatus()->value)
            );
        }

        // Mark the invoice as paid
        $invoice->markAsPaid();
        
        // Set custom paid date if provided
        if ($command->paidAt !== null) {
            $invoice->setPaidAt($command->paidAt);
        }

        $this->entityManager->flush();

        return $invoice;
    }
}