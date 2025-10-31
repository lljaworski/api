<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\Command\Invoice\CreateInvoiceCommand;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Repository\CompanyRepository;
use App\Service\InvoiceNumberGenerator;
use App\Service\VatCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles CreateInvoiceCommand to create a new invoice in the system.
 */
final class CreateInvoiceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepository $companyRepository,
        private readonly InvoiceNumberGenerator $numberGenerator,
        private readonly VatCalculationService $vatCalculationService
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CreateInvoiceCommand $command): mixed
    {
        // Get customer
        $customer = $this->companyRepository->find($command->customerId);
        if (!$customer || $customer->isDeleted()) {
            throw new NotFoundHttpException('Customer not found');
        }

        // Generate invoice number
        $invoiceNumber = $this->numberGenerator->generate($command->issueDate);

        // Create invoice
        $invoice = new Invoice();
        $invoice->setNumber($invoiceNumber);
        $invoice->setIssueDate($command->issueDate);
        $invoice->setSaleDate($command->saleDate);
        $invoice->setCurrency($command->currency);
        $invoice->setCustomer($customer);

        // Set optional fields
        if ($command->dueDate !== null) {
            $invoice->setDueDate($command->dueDate);
        }
        if ($command->paymentMethod !== null) {
            $invoice->setPaymentMethod($command->paymentMethod);
        }
        if ($command->notes !== null) {
            $invoice->setNotes($command->notes);
        }

        // Add invoice items
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

        // Calculate totals
        $this->vatCalculationService->calculateInvoiceTotals($invoice);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }
}