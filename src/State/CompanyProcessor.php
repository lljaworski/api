<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\Company\CreateCompanyCommand;
use App\Application\Command\Company\DeleteCompanyCommand;
use App\Application\Command\Company\UpdateCompanyCommand;
use App\Entity\Company;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @implements ProcessorInterface<Company, Company|void>
 */
final class CompanyProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Company) {
            return $data;
        }

        // Handle delete operations (soft delete)
        if ($operation instanceof DeleteOperationInterface) {
            $command = new DeleteCompanyCommand($data->getId());
            $this->commandBus->dispatch($command);
            return null;
        }

        // Handle create and update operations
        $isUpdate = $data->getId() !== null;
        
        if ($isUpdate) {
            // For updates, create an update command with only the fields that should be updated
            $command = new UpdateCompanyCommand(
                id: $data->getId(),
                name: $this->extractUpdatedName($data, $context),
                taxId: $this->extractUpdatedTaxId($data, $context),
                taxpayerPrefix: $this->extractUpdatedTaxpayerPrefix($data, $context),
                eoriNumber: $this->extractUpdatedEoriNumber($data, $context),
                euCountryCode: $this->extractUpdatedEuCountryCode($data, $context),
                vatRegNumberEu: $this->extractUpdatedVatRegNumberEu($data, $context),
                otherIdCountryCode: $this->extractUpdatedOtherIdCountryCode($data, $context),
                otherIdNumber: $this->extractUpdatedOtherIdNumber($data, $context),
                noIdMarker: $this->extractUpdatedNoIdMarker($data, $context),
                clientNumber: $this->extractUpdatedClientNumber($data, $context),
                countryCode: $this->extractUpdatedCountryCode($data, $context),
                addressLine1: $this->extractUpdatedAddressLine1($data, $context),
                addressLine2: $this->extractUpdatedAddressLine2($data, $context),
                gln: $this->extractUpdatedGln($data, $context),
                correspondenceCountryCode: $this->extractUpdatedCorrespondenceCountryCode($data, $context),
                correspondenceAddressLine1: $this->extractUpdatedCorrespondenceAddressLine1($data, $context),
                correspondenceAddressLine2: $this->extractUpdatedCorrespondenceAddressLine2($data, $context),
                correspondenceGln: $this->extractUpdatedCorrespondenceGln($data, $context),
                email: $this->extractUpdatedEmail($data, $context),
                phoneNumber: $this->extractUpdatedPhoneNumber($data, $context),
                taxpayerStatus: $this->extractUpdatedTaxpayerStatus($data, $context),
                jstMarker: $this->extractUpdatedJstMarker($data, $context),
                gvMarker: $this->extractUpdatedGvMarker($data, $context),
                role: $this->extractUpdatedRole($data, $context),
                otherRoleMarker: $this->extractUpdatedOtherRoleMarker($data, $context),
                roleDescription: $this->extractUpdatedRoleDescription($data, $context),
                sharePercentage: $this->extractUpdatedSharePercentage($data, $context)
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        } else {
            // For creation, create a create command
            $command = new CreateCompanyCommand(
                name: $data->getName(),
                taxId: $data->getTaxId(),
                taxpayerPrefix: $data->getTaxpayerPrefix(),
                eoriNumber: $data->getEoriNumber(),
                euCountryCode: $data->getEuCountryCode(),
                vatRegNumberEu: $data->getVatRegNumberEu(),
                otherIdCountryCode: $data->getOtherIdCountryCode(),
                otherIdNumber: $data->getOtherIdNumber(),
                noIdMarker: $data->getNoIdMarker(),
                clientNumber: $data->getClientNumber(),
                countryCode: $data->getCountryCode(),
                addressLine1: $data->getAddressLine1(),
                addressLine2: $data->getAddressLine2(),
                gln: $data->getGln(),
                correspondenceCountryCode: $data->getCorrespondenceCountryCode(),
                correspondenceAddressLine1: $data->getCorrespondenceAddressLine1(),
                correspondenceAddressLine2: $data->getCorrespondenceAddressLine2(),
                correspondenceGln: $data->getCorrespondenceGln(),
                email: $data->getEmail(),
                phoneNumber: $data->getPhoneNumber(),
                taxpayerStatus: $data->getTaxpayerStatus(),
                jstMarker: $data->getJstMarker(),
                gvMarker: $data->getGvMarker(),
                role: $data->getRole(),
                otherRoleMarker: $data->getOtherRoleMarker(),
                roleDescription: $data->getRoleDescription(),
                sharePercentage: $data->getSharePercentage()
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        }
    }

    // Helper methods to extract updated field values for partial updates
    private function extractUpdatedName(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('name', $context) ? $data->getName() : null;
    }

    private function extractUpdatedTaxId(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('taxId', $context) ? $data->getTaxId() : null;
    }

    private function extractUpdatedTaxpayerPrefix(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('taxpayerPrefix', $context) ? $data->getTaxpayerPrefix() : null;
    }

    private function extractUpdatedEoriNumber(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('eoriNumber', $context) ? $data->getEoriNumber() : null;
    }

    private function extractUpdatedEuCountryCode(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('euCountryCode', $context) ? $data->getEuCountryCode() : null;
    }

    private function extractUpdatedVatRegNumberEu(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('vatRegNumberEu', $context) ? $data->getVatRegNumberEu() : null;
    }

    private function extractUpdatedOtherIdCountryCode(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('otherIdCountryCode', $context) ? $data->getOtherIdCountryCode() : null;
    }

    private function extractUpdatedOtherIdNumber(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('otherIdNumber', $context) ? $data->getOtherIdNumber() : null;
    }

    private function extractUpdatedNoIdMarker(Company $data, array $context): ?bool
    {
        return $this->isFieldInRequest('noIdMarker', $context) ? $data->getNoIdMarker() : null;
    }

    private function extractUpdatedClientNumber(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('clientNumber', $context) ? $data->getClientNumber() : null;
    }

    private function extractUpdatedCountryCode(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('countryCode', $context) ? $data->getCountryCode() : null;
    }

    private function extractUpdatedAddressLine1(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('addressLine1', $context) ? $data->getAddressLine1() : null;
    }

    private function extractUpdatedAddressLine2(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('addressLine2', $context) ? $data->getAddressLine2() : null;
    }

    private function extractUpdatedGln(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('gln', $context) ? $data->getGln() : null;
    }

    private function extractUpdatedCorrespondenceCountryCode(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('correspondenceCountryCode', $context) ? $data->getCorrespondenceCountryCode() : null;
    }

    private function extractUpdatedCorrespondenceAddressLine1(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('correspondenceAddressLine1', $context) ? $data->getCorrespondenceAddressLine1() : null;
    }

    private function extractUpdatedCorrespondenceAddressLine2(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('correspondenceAddressLine2', $context) ? $data->getCorrespondenceAddressLine2() : null;
    }

    private function extractUpdatedCorrespondenceGln(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('correspondenceGln', $context) ? $data->getCorrespondenceGln() : null;
    }

    private function extractUpdatedEmail(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('email', $context) ? $data->getEmail() : null;
    }

    private function extractUpdatedPhoneNumber(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('phoneNumber', $context) ? $data->getPhoneNumber() : null;
    }

    private function extractUpdatedTaxpayerStatus(Company $data, array $context): ?int
    {
        return $this->isFieldInRequest('taxpayerStatus', $context) ? $data->getTaxpayerStatus() : null;
    }

    private function extractUpdatedJstMarker(Company $data, array $context): ?int
    {
        return $this->isFieldInRequest('jstMarker', $context) ? $data->getJstMarker() : null;
    }

    private function extractUpdatedGvMarker(Company $data, array $context): ?int
    {
        return $this->isFieldInRequest('gvMarker', $context) ? $data->getGvMarker() : null;
    }

    private function extractUpdatedRole(Company $data, array $context): ?int
    {
        return $this->isFieldInRequest('role', $context) ? $data->getRole() : null;
    }

    private function extractUpdatedOtherRoleMarker(Company $data, array $context): ?bool
    {
        return $this->isFieldInRequest('otherRoleMarker', $context) ? $data->getOtherRoleMarker() : null;
    }

    private function extractUpdatedRoleDescription(Company $data, array $context): ?string
    {
        return $this->isFieldInRequest('roleDescription', $context) ? $data->getRoleDescription() : null;
    }

    private function extractUpdatedSharePercentage(Company $data, array $context): ?float
    {
        return $this->isFieldInRequest('sharePercentage', $context) ? $data->getSharePercentage() : null;
    }

    /**
     * Check if a field was included in the HTTP request.
     * This is essential for PATCH operations where we only want to update provided fields.
     */
    private function isFieldInRequest(string $fieldName, array $context): bool
    {
        // Check the request content to see if this field was provided
        $request = $context['request'] ?? null;
        
        if (!$request) {
            return true; // If no request context, assume field should be updated (PUT operation)
        }
        
        $content = $request->getContent();
        if (empty($content)) {
            return true;
        }
        
        $data = json_decode($content, true);
        
        if (!is_array($data)) {
            return true;
        }
        
        return array_key_exists($fieldName, $data);
    }
}