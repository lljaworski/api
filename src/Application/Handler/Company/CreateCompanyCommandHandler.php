<?php

declare(strict_types=1);

namespace App\Application\Handler\Company;

use App\Application\Command\Company\CreateCompanyCommand;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles CreateCompanyCommand to create a new company in the system.
 */
final class CreateCompanyCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CreateCompanyCommand $command): mixed
    {
        $company = new Company($command->name);
        
        // Set all the optional fields if provided
        if ($command->taxId !== null) {
            $company->setTaxId($command->taxId);
        }
        if ($command->taxpayerPrefix !== null) {
            $company->setTaxpayerPrefix($command->taxpayerPrefix);
        }
        if ($command->eoriNumber !== null) {
            $company->setEoriNumber($command->eoriNumber);
        }
        if ($command->euCountryCode !== null) {
            $company->setEuCountryCode($command->euCountryCode);
        }
        if ($command->vatRegNumberEu !== null) {
            $company->setVatRegNumberEu($command->vatRegNumberEu);
        }
        if ($command->otherIdCountryCode !== null) {
            $company->setOtherIdCountryCode($command->otherIdCountryCode);
        }
        if ($command->otherIdNumber !== null) {
            $company->setOtherIdNumber($command->otherIdNumber);
        }
        if ($command->noIdMarker !== null) {
            $company->setNoIdMarker($command->noIdMarker);
        }
        if ($command->internalId !== null) {
            $company->setInternalId($command->internalId);
        }
        if ($command->buyerId !== null) {
            $company->setBuyerId($command->buyerId);
        }
        if ($command->clientNumber !== null) {
            $company->setClientNumber($command->clientNumber);
        }
        if ($command->countryCode !== null) {
            $company->setCountryCode($command->countryCode);
        }
        if ($command->addressLine1 !== null) {
            $company->setAddressLine1($command->addressLine1);
        }
        if ($command->addressLine2 !== null) {
            $company->setAddressLine2($command->addressLine2);
        }
        if ($command->gln !== null) {
            $company->setGln($command->gln);
        }
        if ($command->correspondenceCountryCode !== null) {
            $company->setCorrespondenceCountryCode($command->correspondenceCountryCode);
        }
        if ($command->correspondenceAddressLine1 !== null) {
            $company->setCorrespondenceAddressLine1($command->correspondenceAddressLine1);
        }
        if ($command->correspondenceAddressLine2 !== null) {
            $company->setCorrespondenceAddressLine2($command->correspondenceAddressLine2);
        }
        if ($command->correspondenceGln !== null) {
            $company->setCorrespondenceGln($command->correspondenceGln);
        }
        if ($command->email !== null) {
            $company->setEmail($command->email);
        }
        if ($command->phoneNumber !== null) {
            $company->setPhoneNumber($command->phoneNumber);
        }
        if ($command->taxpayerStatus !== null) {
            $company->setTaxpayerStatus($command->taxpayerStatus);
        }
        if ($command->jstMarker !== null) {
            $company->setJstMarker($command->jstMarker);
        }
        if ($command->gvMarker !== null) {
            $company->setGvMarker($command->gvMarker);
        }
        if ($command->role !== null) {
            $company->setRole($command->role);
        }
        if ($command->otherRoleMarker !== null) {
            $company->setOtherRoleMarker($command->otherRoleMarker);
        }
        if ($command->roleDescription !== null) {
            $company->setRoleDescription($command->roleDescription);
        }
        if ($command->sharePercentage !== null) {
            $company->setSharePercentage($command->sharePercentage);
        }
        
        $this->entityManager->persist($company);
        $this->entityManager->flush();
        
        return $company;
    }
}