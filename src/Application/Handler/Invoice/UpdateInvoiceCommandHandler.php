<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\Command\Invoice\UpdateInvoiceCommand;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\CompanyRepository;
use App\Repository\InvoiceRepository;
use App\Service\VatCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles UpdateInvoiceCommand to update an existing invoice.
 */
final class UpdateInvoiceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly VatCalculationService $vatCalculationService
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(UpdateInvoiceCommand $command): mixed
    {
        $invoice = $this->invoiceRepository->find($command->id);
        if (!$invoice || $invoice->isDeleted()) {
            throw new NotFoundHttpException('Invoice not found');
        }

        if (!$invoice->canBeEdited()) {
            throw new \InvalidArgumentException('Invoice cannot be edited in its current state');
        }

        // Update fields if provided
        if ($command->issueDate !== null) {
            $invoice->setIssueDate($command->issueDate);
        }
        if ($command->saleDate !== null) {
            $invoice->setSaleDate($command->saleDate);
        }
        if ($command->dueDate !== null) {
            $invoice->setDueDate($command->dueDate);
        }
        if ($command->currency !== null) {
            $invoice->setCurrency($command->currency);
        }
        if ($command->paymentMethod !== null) {
            $invoice->setPaymentMethod($command->paymentMethod);
        }
        if ($command->notes !== null) {
            $invoice->setNotes($command->notes);
        }
        if ($command->isPaid !== null) {
            $invoice->setIsPaid($command->isPaid);
        }

        // Update customer if provided
        if ($command->customerId !== null) {
            $customer = $this->companyRepository->find($command->customerId);
            if (!$customer || $customer->isDeleted()) {
                throw new NotFoundHttpException('Customer not found');
            }
            $invoice->setCustomer($customer);
        }

        // Update items if provided
        if ($command->items !== null) {
            // Remove existing items
            foreach ($invoice->getItems() as $item) {
                $invoice->removeItem($item);
            }
            
            // Add new items
            foreach ($command->items as $itemData) {
                $item = new InvoiceItem();
                $item->setDescription($itemData['description']);
                $item->setQuantity($itemData['quantity']);
                $item->setUnit($itemData['unit']);
                $item->setUnitPrice($itemData['unitPrice']);
                $item->setVatRate($itemData['vatRate']);
                $item->setSortOrder($itemData['sortOrder']);
                
                $invoice->addItem($item);
            }
        }

        // Recalculate totals if items were updated
        if ($command->items !== null) {
            // Recalculate individual item totals
            foreach ($invoice->getItems() as $item) {
                $this->vatCalculationService->recalculateInvoiceItem($item);
            }
            // Recalculate and update invoice totals
            $this->vatCalculationService->recalculateInvoiceTotals($invoice);
        }

        $this->entityManager->flush();

        return $invoice;
    }
}