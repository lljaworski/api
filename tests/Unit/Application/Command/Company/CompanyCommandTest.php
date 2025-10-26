<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command\Company;

use App\Application\Command\Company\CreateCompanyCommand;
use App\Application\Command\Company\UpdateCompanyCommand;
use App\Application\Command\Company\DeleteCompanyCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyCommandTest extends TestCase
{
    public function testCreateCompanyCommandCanBeCreatedWithRequiredFields(): void
    {
        $command = new CreateCompanyCommand(
            name: 'Test Company Ltd.'
        );

        $this->assertEquals('Test Company Ltd.', $command->name);
        $this->assertNull($command->taxId);
        $this->assertNull($command->email);
        $this->assertNull($command->phoneNumber);
    }

    public function testCreateCompanyCommandCanBeCreatedWithAllFields(): void
    {
        $command = new CreateCompanyCommand(
            name: 'Test Company Ltd.',
            taxId: '1234567890',
            taxpayerPrefix: 'PL',
            eoriNumber: 'PL123456789012345',
            euCountryCode: 'PL',
            vatRegNumberEu: 'PL1234567890',
            otherIdCountryCode: 'US',
            otherIdNumber: 'US123456789',
            noIdMarker: false,
            clientNumber: 'CLI123',
            countryCode: 'PL',
            addressLine1: 'Test Street 123',
            addressLine2: 'Apt 456',
            gln: '1234567890123',
            correspondenceCountryCode: 'PL',
            correspondenceAddressLine1: 'Correspondence Street 789',
            correspondenceAddressLine2: 'Suite 101',
            correspondenceGln: '9876543210987',
            email: 'test@company.com',
            phoneNumber: '+48123456789',
            taxpayerStatus: 1,
            jstMarker: 1,
            gvMarker: 1,
            role: 1,
            otherRoleMarker: true,
            roleDescription: 'Primary contractor',
            sharePercentage: 75.50
        );

        $this->assertEquals('Test Company Ltd.', $command->name);
        $this->assertEquals('1234567890', $command->taxId);
        $this->assertEquals('PL', $command->taxpayerPrefix);
        $this->assertEquals('PL123456789012345', $command->eoriNumber);
        $this->assertEquals('test@company.com', $command->email);
        $this->assertEquals('+48123456789', $command->phoneNumber);
        $this->assertEquals(75.50, $command->sharePercentage);
    }

    public function testUpdateCompanyCommandCanBeCreatedWithId(): void
    {
        $command = new UpdateCompanyCommand(
            id: 1,
            name: 'Updated Company Ltd.',
            taxId: '9876543210',
            email: 'updated@company.com',
            phoneNumber: '+48987654321'
        );

        $this->assertEquals(1, $command->id);
        $this->assertEquals('Updated Company Ltd.', $command->name);
        $this->assertEquals('9876543210', $command->taxId);
        $this->assertEquals('updated@company.com', $command->email);
        $this->assertEquals('+48987654321', $command->phoneNumber);
    }

    public function testUpdateCompanyCommandCanHandleNullValues(): void
    {
        $command = new UpdateCompanyCommand(
            id: 1,
            name: 'Updated Company Ltd.',
            taxId: null,
            email: null,
            phoneNumber: null
        );

        $this->assertEquals(1, $command->id);
        $this->assertEquals('Updated Company Ltd.', $command->name);
        $this->assertNull($command->taxId);
        $this->assertNull($command->email);
        $this->assertNull($command->phoneNumber);
    }

    public function testDeleteCompanyCommandCanBeCreatedWithId(): void
    {
        $command = new DeleteCompanyCommand(id: 123);

        $this->assertEquals(123, $command->id);
    }
}