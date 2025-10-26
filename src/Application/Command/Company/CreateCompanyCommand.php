<?php

declare(strict_types=1);

namespace App\Application\Command\Company;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to create a new company.
 */
final class CreateCompanyCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        
        #[Assert\Length(max: 20)]
        public readonly ?string $taxId = null,
        
        #[Assert\Length(max: 4)]
        public readonly ?string $taxpayerPrefix = null,
        
        #[Assert\Length(max: 17)]
        public readonly ?string $eoriNumber = null,
        
        #[Assert\Length(max: 4)]
        public readonly ?string $euCountryCode = null,
        
        #[Assert\Length(max: 20)]
        public readonly ?string $vatRegNumberEu = null,
        
        #[Assert\Length(max: 4)]
        public readonly ?string $otherIdCountryCode = null,
        
        #[Assert\Length(max: 50)]
        public readonly ?string $otherIdNumber = null,
        
        public readonly ?bool $noIdMarker = null,
        
        #[Assert\Length(max: 50)]
        public readonly ?string $clientNumber = null,
        
        #[Assert\Length(max: 4)]
        public readonly ?string $countryCode = null,
        
        #[Assert\Length(max: 255)]
        public readonly ?string $addressLine1 = null,
        
        #[Assert\Length(max: 255)]
        public readonly ?string $addressLine2 = null,
        
        #[Assert\Length(max: 13)]
        public readonly ?string $gln = null,
        
        #[Assert\Length(max: 4)]
        public readonly ?string $correspondenceCountryCode = null,
        
        #[Assert\Length(max: 255)]
        public readonly ?string $correspondenceAddressLine1 = null,
        
        #[Assert\Length(max: 255)]
        public readonly ?string $correspondenceAddressLine2 = null,
        
        #[Assert\Length(max: 13)]
        public readonly ?string $correspondenceGln = null,
        
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public readonly ?string $email = null,
        
        #[Assert\Length(max: 20)]
        public readonly ?string $phoneNumber = null,
        
        #[Assert\Range(min: 1, max: 4)]
        public readonly ?int $taxpayerStatus = null,
        
        #[Assert\Range(min: 1, max: 2)]
        public readonly ?int $jstMarker = null,
        
        #[Assert\Range(min: 1, max: 2)]
        public readonly ?int $gvMarker = null,
        
        #[Assert\Choice(choices: [1, 2, 4, 6, 11])]
        public readonly ?int $role = null,
        
        public readonly ?bool $otherRoleMarker = null,
        
        #[Assert\Length(max: 255)]
        public readonly ?string $roleDescription = null,
        
        #[Assert\Range(min: 0, max: 100)]
        public readonly ?float $sharePercentage = null
    ) {
        parent::__construct();
    }
}