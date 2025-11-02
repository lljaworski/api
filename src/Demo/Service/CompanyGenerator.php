<?php

declare(strict_types=1);

namespace App\Demo\Service;

use App\Application\Command\Company\CreateCompanyCommand;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Service for generating demo companies with realistic Polish business data.
 */
class CompanyGenerator
{
    private Generator $faker;

    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
        $this->faker = Factory::create('pl_PL'); // Polish locale for realistic data
    }

    /**
     * Generate a realistic Polish company name.
     */
    public function generateCompanyName(): string
    {
        $companyTypes = ['Sp. z o.o.', 'S.A.', 'Sp. j.', 'Sp. p.', 'Sp. k.', 'P.P.H.U.'];
        $businessSuffixes = ['Group', 'Solutions', 'Services', 'Tech', 'Systems', 'Industries', 'Trading'];
        
        // 70% chance for realistic company name, 30% for more creative combinations
        if ($this->faker->boolean(70)) {
            return $this->faker->company();
        }
        
        $baseName = $this->faker->lastName() . ' ' . $this->faker->randomElement($businessSuffixes);
        $companyType = $this->faker->randomElement($companyTypes);
        
        return $baseName . ' ' . $companyType;
    }

    /**
     * Generate a valid Polish NIP (tax identification number).
     */
    public function generateNIP(): string
    {
        // Generate 9 digits
        $digits = [];
        for ($i = 0; $i < 9; $i++) {
            $digits[] = $this->faker->numberBetween(0, 9);
        }

        // Calculate checksum digit
        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $digits[$i] * $weights[$i];
        }
        $checksum = $sum % 11;
        
        if ($checksum == 10) {
            // If checksum is 10, regenerate (invalid NIP)
            return $this->generateNIP();
        }
        
        $digits[] = $checksum;
        
        return implode('', $digits);
    }

    /**
     * Generate a valid Polish postal code.
     */
    public function generatePolishPostalCode(): string
    {
        return sprintf('%02d-%03d', 
            $this->faker->numberBetween(0, 99), 
            $this->faker->numberBetween(0, 999)
        );
    }

    /**
     * Generate a Polish address.
     */
    public function generatePolishAddress(): array
    {
        return [
            'countryCode' => 'PL',
            'addressLine1' => $this->faker->streetAddress(),
            'addressLine2' => $this->faker->boolean(30) ? 'Apt ' . $this->faker->buildingNumber() : null,
        ];
    }

    /**
     * Generate a GLN (Global Location Number).
     */
    public function generateGLN(): string
    {
        // Generate 12 digits
        $digits = [];
        for ($i = 0; $i < 12; $i++) {
            $digits[] = $this->faker->numberBetween(0, 9);
        }

        // Calculate checksum digit (simplified)
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digits[$i] * $weight;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        $digits[] = $checksum;

        return implode('', $digits);
    }

    /**
     * Generate EORI number.
     */
    public function generateEORI(): string
    {
        return 'PL' . $this->faker->numerify('###############');
    }

    /**
     * Generate VAT registration number for EU.
     */
    public function generateVATRegNumberEU(): string
    {
        $euCountries = ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'SE', 'DK'];
        $country = $this->faker->randomElement($euCountries);
        
        return $country . $this->faker->numerify('##########');
    }

    /**
     * Generate complete company data.
     */
    public function generateCompanyData(): array
    {
        $primaryAddress = $this->generatePolishAddress();
        $hasCorrespondenceAddress = $this->faker->boolean(40);
        $correspondenceAddress = $hasCorrespondenceAddress ? $this->generatePolishAddress() : null;

        return [
            'name' => $this->generateCompanyName(),
            'taxId' => $this->generateNIP(),
            'taxpayerPrefix' => $this->faker->boolean(80) ? 'PL' : null,
            'eoriNumber' => $this->faker->boolean(30) ? $this->generateEORI() : null,
            'euCountryCode' => $this->faker->boolean(40) ? $this->faker->randomElement(['DE', 'FR', 'IT', 'ES']) : null,
            'vatRegNumberEu' => $this->faker->boolean(30) ? $this->generateVATRegNumberEU() : null,
            'otherIdCountryCode' => $this->faker->boolean(20) ? $this->faker->randomElement(['US', 'UK', 'CA']) : null,
            'otherIdNumber' => $this->faker->boolean(20) ? $this->faker->numerify('##########') : null,
            'noIdMarker' => $this->faker->boolean(10),
            'clientNumber' => $this->faker->boolean(60) ? 'CLI' . $this->faker->numerify('######') : null,
            
            // Primary address
            'countryCode' => $primaryAddress['countryCode'],
            'addressLine1' => $primaryAddress['addressLine1'],
            'addressLine2' => $primaryAddress['addressLine2'],
            'gln' => $this->faker->boolean(40) ? $this->generateGLN() : null,
            
            // Correspondence address
            'correspondenceCountryCode' => $correspondenceAddress['countryCode'] ?? null,
            'correspondenceAddressLine1' => $correspondenceAddress['addressLine1'] ?? null,
            'correspondenceAddressLine2' => $correspondenceAddress['addressLine2'] ?? null,
            'correspondenceGln' => $hasCorrespondenceAddress && $this->faker->boolean(40) ? $this->generateGLN() : null,
            
            // Contact details
            'email' => $this->faker->companyEmail(),
            'phoneNumber' => $this->faker->boolean(90) ? $this->faker->phoneNumber() : null,
            
            // Additional information
            'taxpayerStatus' => $this->faker->numberBetween(1, 4),
            'jstMarker' => $this->faker->boolean(70) ? $this->faker->numberBetween(1, 2) : null,
            'gvMarker' => $this->faker->boolean(60) ? $this->faker->numberBetween(1, 2) : null,
            'role' => $this->faker->boolean(80) ? $this->faker->randomElement([1, 2, 4, 6, 11]) : null,
            'otherRoleMarker' => $this->faker->boolean(20),
            'roleDescription' => $this->faker->boolean(30) ? $this->faker->jobTitle() : null,
            'sharePercentage' => $this->faker->boolean(50) ? $this->faker->randomFloat(2, 0, 100) : null,
        ];
    }

    /**
     * Create a single demo company.
     * 
     * @throws \Exception if company creation fails
     */
    public function createCompany(?array $companyData = null): array
    {
        $data = $companyData ?? $this->generateCompanyData();
        
        $command = new CreateCompanyCommand(
            name: $data['name'],
            taxId: $data['taxId'],
            taxpayerPrefix: $data['taxpayerPrefix'],
            eoriNumber: $data['eoriNumber'],
            euCountryCode: $data['euCountryCode'],
            vatRegNumberEu: $data['vatRegNumberEu'],
            otherIdCountryCode: $data['otherIdCountryCode'],
            otherIdNumber: $data['otherIdNumber'],
            noIdMarker: $data['noIdMarker'],
            clientNumber: $data['clientNumber'],
            countryCode: $data['countryCode'],
            addressLine1: $data['addressLine1'],
            addressLine2: $data['addressLine2'],
            gln: $data['gln'],
            correspondenceCountryCode: $data['correspondenceCountryCode'],
            correspondenceAddressLine1: $data['correspondenceAddressLine1'],
            correspondenceAddressLine2: $data['correspondenceAddressLine2'],
            correspondenceGln: $data['correspondenceGln'],
            email: $data['email'],
            phoneNumber: $data['phoneNumber'],
            taxpayerStatus: $data['taxpayerStatus'],
            jstMarker: $data['jstMarker'],
            gvMarker: $data['gvMarker'],
            role: $data['role'],
            otherRoleMarker: $data['otherRoleMarker'],
            roleDescription: $data['roleDescription'],
            sharePercentage: $data['sharePercentage']
        );

        $envelope = $this->commandBus->dispatch($command);
        $company = $envelope->last(HandledStamp::class)->getResult();

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'taxId' => $company->getTaxId(),
            'email' => $company->getEmail(),
            'created' => true
        ];
    }
}