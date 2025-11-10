<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Application\Command\Company\CreateCompanyCommand;
use App\Application\Command\Company\UpdateCompanyCommand;
use App\Entity\Company;
use App\Service\CompanyHydrator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CompanyHydrator service.
 */
class CompanyHydratorTest extends TestCase
{
    private CompanyHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new CompanyHydrator();
    }

    public function testHydrateFromCreateCommandWithAllFields(): void
    {
        $command = new CreateCompanyCommand(
            name: 'Test Company',
            taxId: '1234567890',
            taxpayerPrefix: 'PL',
            eoriNumber: 'PL1234567890123',
            euCountryCode: 'PL',
            vatRegNumberEu: 'EU123456789',
            otherIdCountryCode: 'DE',
            otherIdNumber: 'OTHER123',
            noIdMarker: true,
            clientNumber: 'CLIENT001',
            countryCode: 'PL',
            addressLine1: 'Main Street 1',
            addressLine2: 'Apt 2',
            gln: '1234567890123',
            correspondenceCountryCode: 'PL',
            correspondenceAddressLine1: 'Correspondence Street 1',
            correspondenceAddressLine2: 'Correspondence Apt 2',
            correspondenceGln: '9876543210987',
            email: 'test@company.com',
            phoneNumber: '+48123456789',
            taxpayerStatus: 1,
            jstMarker: 1,
            gvMarker: 2,
            role: 1,
            otherRoleMarker: false,
            roleDescription: 'Main role',
            sharePercentage: 75.5
        );

        $company = new Company('Test Company');
        $this->hydrator->hydrateFromCreateCommand($company, $command);

        $this->assertEquals('1234567890', $company->getTaxId());
        $this->assertEquals('PL', $company->getTaxpayerPrefix());
        $this->assertEquals('PL1234567890123', $company->getEoriNumber());
        $this->assertEquals('PL', $company->getEuCountryCode());
        $this->assertEquals('EU123456789', $company->getVatRegNumberEu());
        $this->assertEquals('DE', $company->getOtherIdCountryCode());
        $this->assertEquals('OTHER123', $company->getOtherIdNumber());
        $this->assertTrue($company->getNoIdMarker());
        $this->assertEquals('CLIENT001', $company->getClientNumber());
        $this->assertEquals('PL', $company->getCountryCode());
        $this->assertEquals('Main Street 1', $company->getAddressLine1());
        $this->assertEquals('Apt 2', $company->getAddressLine2());
        $this->assertEquals('1234567890123', $company->getGln());
        $this->assertEquals('PL', $company->getCorrespondenceCountryCode());
        $this->assertEquals('Correspondence Street 1', $company->getCorrespondenceAddressLine1());
        $this->assertEquals('Correspondence Apt 2', $company->getCorrespondenceAddressLine2());
        $this->assertEquals('9876543210987', $company->getCorrespondenceGln());
        $this->assertEquals('test@company.com', $company->getEmail());
        $this->assertEquals('+48123456789', $company->getPhoneNumber());
        $this->assertEquals(1, $company->getTaxpayerStatus());
        $this->assertEquals(1, $company->getJstMarker());
        $this->assertEquals(2, $company->getGvMarker());
        $this->assertEquals(1, $company->getRole());
        $this->assertFalse($company->getOtherRoleMarker());
        $this->assertEquals('Main role', $company->getRoleDescription());
        $this->assertEquals(75.5, $company->getSharePercentage());
    }

    public function testHydrateFromCreateCommandWithNullFields(): void
    {
        $command = new CreateCompanyCommand(
            name: 'Test Company'
        );

        $company = new Company('Test Company');
        $this->hydrator->hydrateFromCreateCommand($company, $command);

        // All optional fields should remain null
        $this->assertNull($company->getTaxId());
        $this->assertNull($company->getTaxpayerPrefix());
        $this->assertNull($company->getEoriNumber());
        $this->assertNull($company->getEmail());
        $this->assertNull($company->getPhoneNumber());
    }

    public function testHydrateFromUpdateCommandWithAllFields(): void
    {
        $command = new UpdateCompanyCommand(
            id: 1,
            name: 'Updated Company',
            taxId: '9876543210',
            email: 'updated@company.com',
            phoneNumber: '+48987654321',
            taxpayerStatus: 2
        );

        $company = new Company('Original Company');
        $this->hydrator->hydrateFromUpdateCommand($company, $command);

        $this->assertEquals('Updated Company', $company->getName());
        $this->assertEquals('9876543210', $company->getTaxId());
        $this->assertEquals('updated@company.com', $company->getEmail());
        $this->assertEquals('+48987654321', $company->getPhoneNumber());
        $this->assertEquals(2, $company->getTaxpayerStatus());
    }

    public function testHydrateFromUpdateCommandWithPartialFields(): void
    {
        $company = new Company('Original Company');
        $company->setTaxId('original-tax-id');
        $company->setEmail('original@company.com');
        $company->setPhoneNumber('+48111111111');

        $command = new UpdateCompanyCommand(
            id: 1,
            // Only update email and leave other fields null
            email: 'updated@company.com'
        );

        $this->hydrator->hydrateFromUpdateCommand($company, $command);

        // Only email should be updated
        $this->assertEquals('Original Company', $company->getName());
        $this->assertEquals('original-tax-id', $company->getTaxId());
        $this->assertEquals('updated@company.com', $company->getEmail());
        $this->assertEquals('+48111111111', $company->getPhoneNumber());
    }

    public function testHydrateFromUpdateCommandWithNullName(): void
    {
        $company = new Company('Original Company');
        
        $command = new UpdateCompanyCommand(
            id: 1,
            name: null, // Don't update name
            taxId: 'new-tax-id'
        );

        $this->hydrator->hydrateFromUpdateCommand($company, $command);

        // Name should remain unchanged since it was null in command
        $this->assertEquals('Original Company', $company->getName());
        $this->assertEquals('new-tax-id', $company->getTaxId());
    }

    public function testHydrateFromUpdateCommandUpdatesName(): void
    {
        $company = new Company('Original Company');
        
        $command = new UpdateCompanyCommand(
            id: 1,
            name: 'Updated Company Name'
        );

        $this->hydrator->hydrateFromUpdateCommand($company, $command);

        $this->assertEquals('Updated Company Name', $company->getName());
    }
}