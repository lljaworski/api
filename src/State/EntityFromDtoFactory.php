<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\CompanyDTO;
use App\Application\DTO\UserDTO;
use App\Entity\Company;
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
            'internalId' => 'setInternalId',
            'buyerId' => 'setBuyerId',
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
     * Helper method to set private properties via reflection.
     */
    private static function setPrivateProperty(\ReflectionClass $reflection, object $object, string $propertyName, mixed $value): void
    {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}