<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\Command\Invoice\DeleteInvoiceCommand;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles DeleteInvoiceCommand to soft delete an invoice.
 */
final class DeleteInvoiceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepository $invoiceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(DeleteInvoiceCommand $command): void
    {
        $invoice = $this->invoiceRepository->find($command->id);
        if (!$invoice || $invoice->isDeleted()) {
            throw new NotFoundHttpException('Invoice not found');
        }

        if (!$invoice->canBeDeleted()) {
            throw new \InvalidArgumentException('Invoice cannot be deleted in its current state');
        }

        $invoice->softDelete();
        $this->entityManager->flush();
    }
}