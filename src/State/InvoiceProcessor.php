<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\Invoice\CreateInvoiceCommand;
use App\Application\Command\Invoice\DeleteInvoiceCommand;
use App\Application\Command\Invoice\UpdateInvoiceCommand;
use App\Entity\Invoice;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @implements ProcessorInterface<Invoice, Invoice|void>
 */
final class InvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Invoice) {
            return $data;
        }

        // Handle delete operations (soft delete)
        if ($operation instanceof DeleteOperationInterface) {
            $command = new DeleteInvoiceCommand($data->getId());
            $this->commandBus->dispatch($command);
            return null;
        }

        // Handle create and update operations
        $isUpdate = $data->getId() !== null;
        
        if ($isUpdate) {
            // For updates, create an update command with only the fields that should be updated
            $command = new UpdateInvoiceCommand(
                id: $data->getId(),
                issueDate: UpdatedFieldExtractor::extract($data, $context, 'issueDate', 'getIssueDate'),
                saleDate: UpdatedFieldExtractor::extract($data, $context, 'saleDate', 'getSaleDate'),
                dueDate: UpdatedFieldExtractor::extract($data, $context, 'dueDate', 'getDueDate'),
                currency: UpdatedFieldExtractor::extract($data, $context, 'currency', 'getCurrency'),
                paymentMethod: UpdatedFieldExtractor::extract($data, $context, 'paymentMethod', 'getPaymentMethod'),
                notes: UpdatedFieldExtractor::extract($data, $context, 'notes', 'getNotes'),
                isPaid: UpdatedFieldExtractor::extract($data, $context, 'isPaid', 'isPaid'),
                customerId: $this->getUpdatedCustomerId($context, $data),
                items: $this->getUpdatedItems($context, $data)
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        } else {
            // For creation, create a create command
            $command = new CreateInvoiceCommand(
                issueDate: $data->getIssueDate(),
                saleDate: $data->getSaleDate(),
                dueDate: $data->getDueDate(),
                currency: $data->getCurrency(),
                paymentMethod: $data->getPaymentMethod(),
                notes: $data->getNotes(),
                customerId: $data->getCustomer()->getId(),
                items: $this->extractInvoiceItems($data)
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        }
    }
    
    /**
     * Extract customer ID from request context.
     */
    private function getUpdatedCustomerId(array $context, Invoice $invoice): ?int
    {
        $requestData = $context['request']?->getContent();
        if ($requestData === null) {
            return null;
        }
        
        try {
            $decodedData = json_decode($requestData, true, 512, JSON_THROW_ON_ERROR);
            if (isset($decodedData['customer'])) {
                $customer = $decodedData['customer'];
                
                // Handle IRI format (e.g., "/api/companies/1")
                if (is_string($customer) && preg_match('/\/api\/companies\/(\d+)$/', $customer, $matches)) {
                    return (int) $matches[1];
                }
                
                // Handle array format with ID
                if (is_array($customer) && isset($customer['id'])) {
                    return (int) $customer['id'];
                }
                
                // Handle direct integer ID
                if (is_int($customer)) {
                    return $customer;
                }
            }
        } catch (\JsonException) {
            // Ignore JSON errors
        }
        
        return null; // Don't update customer if not provided
    }
    
    /**
     * Extract invoice items from request context.
     */
    private function getUpdatedItems(array $context, Invoice $invoice): ?array
    {
        $requestData = $context['request']?->getContent();
        if ($requestData === null) {
            return null;
        }
        
        try {
            $decodedData = json_decode($requestData, true, 512, JSON_THROW_ON_ERROR);
            if (isset($decodedData['items'])) {
                return $this->extractInvoiceItemsFromArray($decodedData['items']);
            }
        } catch (\JsonException) {
            // Ignore JSON errors
        }
        
        return null; // Don't update items if not provided
    }
    
    /**
     * Extract invoice items from the entity for creation.
     */
    private function extractInvoiceItems(Invoice $invoice): array
    {
        $items = [];
        
        foreach ($invoice->getItems() as $item) {
            $items[] = [
                'description' => $item->getDescription(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'vatRate' => $item->getVatRate(),
                'unit' => $item->getUnit(),
                'sortOrder' => $item->getSortOrder()
            ];
        }
        
        return $items;
    }
    
    /**
     * Extract invoice items from array data.
     */
    private function extractInvoiceItemsFromArray(array $itemsData): array
    {
        $items = [];
        
        foreach ($itemsData as $itemData) {
            $items[] = [
                'description' => $itemData['description'] ?? '',
                'quantity' => $itemData['quantity'] ?? '1.000',
                'unitPrice' => $itemData['unitPrice'] ?? '0.00',
                'vatRate' => $itemData['vatRate'] ?? '23.00',
                'unit' => $itemData['unit'] ?? 'szt.',
                'sortOrder' => $itemData['sortOrder'] ?? 0
            ];
        }
        
        return $items;
    }
}