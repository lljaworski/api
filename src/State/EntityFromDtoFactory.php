<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\CompanyDTO;
use App\Application\DTO\InvoiceDTO;
use App\Application\DTO\InvoiceItemDTO;
use App\Application\DTO\SystemPreferenceDTO;
use App\Application\DTO\UserDTO;
use App\Entity\Company;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\SystemPreference;
use App\Entity\User;

/**
 * Factory for creating entities from DTOs with reflection-based property setting.
 * This reduces code duplication in State Providers.
 */
final class EntityFromDtoFactory
{
    /**
     * Creates a User entity from UserDTO with all properties set via reflection.
     */
    public static function createUserFromDTO(UserDTO $dto): User
    {
        $user = new User($dto->username, ''); // Password not needed for read operations
        
        // Use reflection to set private properties since this is for read-only display
        $reflection = new \ReflectionClass($user);
        
        self::setPrivateProperty($reflection, $user, 'id', $dto->id);
        self::setPrivateProperty($reflection, $user, 'createdAt', $dto->createdAt);
        self::setPrivateProperty($reflection, $user, 'updatedAt', $dto->updatedAt);
        
        if ($dto->deletedAt) {
            self::setPrivateProperty($reflection, $user, 'deletedAt', $dto->deletedAt);
        }
        
        // Set roles using public setter
        $user->setRoles($dto->roles);
        
        return $user;
    }
    
    /**
     * Creates a Company entity from CompanyDTO with all properties set via reflection.
     */
    public static function createCompanyFromDTO(CompanyDTO $dto): Company
    {
        $company = new Company($dto->name);
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($company);
        
        self::setPrivateProperty($reflection, $company, 'id', $dto->id);
        
        // Set all nullable properties using public setters
        $propertyMappings = [
            'taxId' => 'setTaxId',
            'taxpayerPrefix' => 'setTaxpayerPrefix',
            'eoriNumber' => 'setEoriNumber',
            'euCountryCode' => 'setEuCountryCode',
            'vatRegNumberEu' => 'setVatRegNumberEu',
            'otherIdCountryCode' => 'setOtherIdCountryCode',
            'otherIdNumber' => 'setOtherIdNumber',
            'noIdMarker' => 'setNoIdMarker',
            'clientNumber' => 'setClientNumber',
            'countryCode' => 'setCountryCode',
            'addressLine1' => 'setAddressLine1',
            'addressLine2' => 'setAddressLine2',
            'gln' => 'setGln',
            'correspondenceCountryCode' => 'setCorrespondenceCountryCode',
            'correspondenceAddressLine1' => 'setCorrespondenceAddressLine1',
            'correspondenceAddressLine2' => 'setCorrespondenceAddressLine2',
            'correspondenceGln' => 'setCorrespondenceGln',
            'email' => 'setEmail',
            'phoneNumber' => 'setPhoneNumber',
            'taxpayerStatus' => 'setTaxpayerStatus',
            'jstMarker' => 'setJstMarker',
            'gvMarker' => 'setGvMarker',
            'role' => 'setRole',
            'otherRoleMarker' => 'setOtherRoleMarker',
            'roleDescription' => 'setRoleDescription',
            'sharePercentage' => 'setSharePercentage',
        ];
        
        foreach ($propertyMappings as $dtoProperty => $setterMethod) {
            $value = $dto->$dtoProperty;
            if ($value !== null) {
                $company->$setterMethod($value);
            }
        }
        
        // Set audit fields with DateTime conversion
        self::setPrivateProperty(
            $reflection, 
            $company, 
            'createdAt', 
            \DateTime::createFromImmutable($dto->createdAt)
        );
        self::setPrivateProperty(
            $reflection, 
            $company, 
            'updatedAt', 
            \DateTime::createFromImmutable($dto->updatedAt)
        );
        
        if ($dto->deletedAt !== null) {
            self::setPrivateProperty(
                $reflection, 
                $company, 
                'deletedAt', 
                \DateTime::createFromImmutable($dto->deletedAt)
            );
        }
        
        return $company;
    }
    
    /**
     * Creates an Invoice entity from InvoiceDTO with all properties set via reflection.
     */
    public static function createInvoiceFromDTO(InvoiceDTO $dto): Invoice
    {
        $invoice = new Invoice();
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($invoice);
        
        self::setPrivateProperty($reflection, $invoice, 'id', $dto->id);
        
        // Set all properties using public setters where available
        $invoice->setNumber($dto->number);
        $invoice->setIssueDate($dto->issueDate);
        $invoice->setSaleDate($dto->saleDate);
        $invoice->setCurrency($dto->currency);
        $invoice->setSubtotal($dto->subtotal);
        $invoice->setVatAmount($dto->vatAmount);
        $invoice->setTotal($dto->total);
        $invoice->setIsPaid($dto->isPaid);
        
        // Set optional properties
        if ($dto->dueDate !== null) {
            $invoice->setDueDate($dto->dueDate);
        }
        if ($dto->paymentMethod !== null) {
            $invoice->setPaymentMethod($dto->paymentMethod);
        }
        if ($dto->notes !== null) {
            $invoice->setNotes($dto->notes);
        }
        if ($dto->paidAt !== null) {
            $invoice->setPaidAt($dto->paidAt);
        }
        if ($dto->ksefNumber !== null) {
            $invoice->setKsefNumber($dto->ksefNumber);
        }
        if ($dto->ksefSubmittedAt !== null) {
            $invoice->setKsefSubmittedAt($dto->ksefSubmittedAt);
        }
        
        // Set status using reflection (no public setter that bypasses validation)
        self::setPrivateProperty($reflection, $invoice, 'status', $dto->status);
        
        // Set audit fields
        self::setPrivateProperty($reflection, $invoice, 'createdAt', $dto->createdAt);
        self::setPrivateProperty($reflection, $invoice, 'updatedAt', $dto->updatedAt);
        
        if ($dto->deletedAt !== null) {
            self::setPrivateProperty($reflection, $invoice, 'deletedAt', $dto->deletedAt);
        }
        
        // Set customer relationship (assuming the DTO has customer data)
        if ($dto->customer !== null) {
            $customer = self::createCompanyFromDTO($dto->customer);
            $invoice->setCustomer($customer);
        }
        
        // Set invoice items from DTOs
        foreach ($dto->items as $itemDto) {
            $item = self::createInvoiceItemFromDTO($itemDto);
            $invoice->addItem($item);
        }
        
        return $invoice;
    }
    
    /**
     * Creates an InvoiceItem entity from InvoiceItemDTO.
     */
    public static function createInvoiceItemFromDTO(InvoiceItemDTO $dto): InvoiceItem
    {
        $item = new InvoiceItem();
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($item);
        
        self::setPrivateProperty($reflection, $item, 'id', $dto->id);
        
        // Set all properties using public setters
        $item->setDescription($dto->description);
        $item->setQuantity($dto->quantity);
        $item->setUnit($dto->unit);
        $item->setUnitPrice($dto->unitPrice);
        $item->setNetAmount($dto->netAmount);
        $item->setVatRate($dto->vatRate);
        $item->setVatAmount($dto->vatAmount);
        $item->setGrossAmount($dto->grossAmount);
        $item->setSortOrder($dto->sortOrder);
        
        return $item;
    }
    
    /**
     * Creates a SystemPreference entity from SystemPreferenceDTO with all properties set via reflection.
     */
    public static function createSystemPreferenceFromDTO(SystemPreferenceDTO $dto): SystemPreference
    {
        $preference = new SystemPreference($dto->preferenceKey, $dto->value);
        
        // Use reflection to set private properties since this is for read-only display
        $reflection = new \ReflectionClass($preference);
        
        self::setPrivateProperty($reflection, $preference, 'id', $dto->id);
        self::setPrivateProperty(
            $reflection, 
            $preference, 
            'createdAt', 
            \DateTime::createFromImmutable($dto->createdAt)
        );
        self::setPrivateProperty(
            $reflection, 
            $preference, 
            'updatedAt', 
            \DateTime::createFromImmutable($dto->updatedAt)
        );
        
        return $preference;
    }
    
    /**
     * Helper method to set private properties via reflection.
     */
    private static function setPrivateProperty(\ReflectionClass $reflection, object $object, string $propertyName, mixed $value): void
    {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}