<?php

declare(strict_types=1);

namespace App\Application\Handler\Company;

use App\Application\DTO\CompanyCollectionDTO;
use App\Application\DTO\CompanyDTO;
use App\Application\Query\Company\GetCompaniesQuery;
use App\Entity\Company;
use App\Repository\CompanyRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetCompaniesQuery to retrieve a paginated list of companies.
 */
final class GetCompaniesQueryHandler
{
    public function __construct(
        private readonly CompanyRepository $companyRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetCompaniesQuery $query): mixed
    {
        $offset = ($query->page - 1) * $query->itemsPerPage;
        
        $companies = $this->companyRepository->findActiveCompanies(
            $query->itemsPerPage,
            $offset,
            $query->search
        );
        
        $total = $this->companyRepository->countActiveCompanies($query->search);
        
        $companyDTOs = array_map([$this, 'mapCompanyToDTO'], $companies);
        
        return new CompanyCollectionDTO(
            companies: $companyDTOs,
            total: $total,
            page: $query->page,
            itemsPerPage: $query->itemsPerPage
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
            internalId: $company->getInternalId(),
            buyerId: $company->getBuyerId(),
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
}