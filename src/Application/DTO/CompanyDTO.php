<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for Company data.
 */
final class CompanyDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $taxId,
        public readonly ?string $taxpayerPrefix,
        public readonly ?string $eoriNumber,
        public readonly ?string $euCountryCode,
        public readonly ?string $vatRegNumberEu,
        public readonly ?string $otherIdCountryCode,
        public readonly ?string $otherIdNumber,
        public readonly ?bool $noIdMarker,
        public readonly ?string $clientNumber,
        public readonly ?string $countryCode,
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $gln,
        public readonly ?string $correspondenceCountryCode,
        public readonly ?string $correspondenceAddressLine1,
        public readonly ?string $correspondenceAddressLine2,
        public readonly ?string $correspondenceGln,
        public readonly ?string $email,
        public readonly ?string $phoneNumber,
        public readonly ?int $taxpayerStatus,
        public readonly ?int $jstMarker,
        public readonly ?int $gvMarker,
        public readonly ?int $role,
        public readonly ?bool $otherRoleMarker,
        public readonly ?string $roleDescription,
        public readonly ?float $sharePercentage,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null
    ) {
    }
    
    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }
}