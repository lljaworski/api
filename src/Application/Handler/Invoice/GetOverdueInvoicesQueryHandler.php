<?php

declare(strict_types=1);

namespace App\Application\Handler\Invoice;

use App\Application\DTO\CompanyDTO;
use App\Application\DTO\InvoiceCollectionDTO;
use App\Application\DTO\InvoiceDTO;
use App\Application\DTO\InvoiceItemDTO;
use App\Application\Query\Invoice\GetOverdueInvoicesQuery;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\Company;
use App\Repository\InvoiceRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetOverdueInvoicesQuery to retrieve overdue invoices.
 */
final class GetOverdueInvoicesQueryHandler
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetOverdueInvoicesQuery $query): InvoiceCollectionDTO
    {
        $invoices = $this->invoiceRepository->findOverdueInvoices();
        $total = $this->invoiceRepository->countOverdueInvoices();
        
        $invoiceDTOs = array_map([$this, 'mapInvoiceToDTO'], $invoices);
        
        return new InvoiceCollectionDTO(
            invoices: $invoiceDTOs,
            total: $total,
            page: 1,
            itemsPerPage: count($invoices)
        );
    }
    
    private function mapInvoiceToDTO(Invoice $invoice): InvoiceDTO
    {
        return new InvoiceDTO(
            id: $invoice->getId(),
            number: $invoice->getNumber(),
            issueDate: $invoice->getIssueDate(),
            saleDate: $invoice->getSaleDate(),
            dueDate: $invoice->getDueDate(),
            currency: $invoice->getCurrency(),
            paymentMethod: $invoice->getPaymentMethod(),
            status: $invoice->getStatus(),
            isPaid: $invoice->isPaid(),
            paidAt: $invoice->getPaidAt(),
            notes: $invoice->getNotes(),
            ksefNumber: $invoice->getKsefNumber(),
            ksefSubmittedAt: $invoice->getKsefSubmittedAt(),
            subtotal: $invoice->getSubtotal(),
            vatAmount: $invoice->getVatAmount(),
            total: $invoice->getTotal(),
            customer: $this->mapCompanyToDTO($invoice->getCustomer()),
            items: array_map([$this, 'mapInvoiceItemToDTO'], $invoice->getItems()->toArray()),
            createdAt: \DateTimeImmutable::createFromInterface($invoice->getCreatedAt()),
            updatedAt: \DateTimeImmutable::createFromInterface($invoice->getUpdatedAt()),
            deletedAt: $invoice->getDeletedAt() ? \DateTimeImmutable::createFromInterface($invoice->getDeletedAt()) : null
        );
    }
    
    private function mapCompanyToDTO(Company $company): CompanyDTO
    {
        return new CompanyDTO(
            id: $company->getId(),
            name: $company->getName(),
            taxId: $company->getTaxId(),
            taxpayerPrefix: $company->getTaxpayerPrefix(),
            eoriNumber: $company->getEoriNumber(),
            euCountryCode: $company->getEuCountryCode(),
            vatRegNumberEu: $company->getVatRegNumberEu(),
            otherIdCountryCode: $company->getOtherIdCountryCode(),
            otherIdNumber: $company->getOtherIdNumber(),
            noIdMarker: $company->getNoIdMarker(),
            clientNumber: $company->getClientNumber(),
            countryCode: $company->getCountryCode(),
            addressLine1: $company->getAddressLine1(),
            addressLine2: $company->getAddressLine2(),
            gln: $company->getGln(),
            correspondenceCountryCode: $company->getCorrespondenceCountryCode(),
            correspondenceAddressLine1: $company->getCorrespondenceAddressLine1(),
            correspondenceAddressLine2: $company->getCorrespondenceAddressLine2(),
            correspondenceGln: $company->getCorrespondenceGln(),
            email: $company->getEmail(),
            phoneNumber: $company->getPhoneNumber(),
            taxpayerStatus: $company->getTaxpayerStatus(),
            jstMarker: $company->getJstMarker(),
            gvMarker: $company->getGvMarker(),
            role: $company->getRole(),
            otherRoleMarker: $company->getOtherRoleMarker(),
            roleDescription: $company->getRoleDescription(),
            sharePercentage: $company->getSharePercentage(),
            createdAt: \DateTimeImmutable::createFromInterface($company->getCreatedAt()),
            updatedAt: \DateTimeImmutable::createFromInterface($company->getUpdatedAt()),
            deletedAt: $company->getDeletedAt() ? \DateTimeImmutable::createFromInterface($company->getDeletedAt()) : null
        );
    }
    
    private function mapInvoiceItemToDTO(InvoiceItem $item): InvoiceItemDTO
    {
        return new InvoiceItemDTO(
            id: $item->getId(),
            description: $item->getDescription(),
            quantity: $item->getQuantity(),
            unit: $item->getUnit(),
            unitPrice: $item->getUnitPrice(),
            netAmount: $item->getNetAmount(),
            vatRate: $item->getVatRate(),
            vatAmount: $item->getVatAmount(),
            grossAmount: $item->getGrossAmount(),
            sortOrder: $item->getSortOrder()
        );
    }
}