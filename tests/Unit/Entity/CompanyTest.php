<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Company;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyTest extends TestCase
{
    public function testCompanyCanBeCreatedWithRequiredFields(): void
    {
        $company = new Company('Test Company Ltd.');
        
        $this->assertEquals('Test Company Ltd.', $company->getName());
        $this->assertNull($company->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $company->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $company->getUpdatedAt());
        $this->assertNull($company->getDeletedAt());
        $this->assertTrue($company->isActive());
        $this->assertFalse($company->isDeleted());
    }

    public function testCompanyCanBeCreatedWithAllFields(): void
    {
        $company = new Company('Test Company Ltd.');
        $company->setTaxId('1234567890');
        $company->setTaxpayerPrefix('PL');
        $company->setEoriNumber('PL123456789012345');
        $company->setEuCountryCode('PL');
        $company->setVatRegNumberEu('PL1234567890');
        $company->setOtherIdCountryCode('US');
        $company->setOtherIdNumber('US123456789');
        $company->setNoIdMarker(false);
        $company->setInternalId('INT123');
        $company->setBuyerId('BUY123');
        $company->setClientNumber('CLI123');
        $company->setCountryCode('PL');
        $company->setAddressLine1('Test Street 123');
        $company->setAddressLine2('Apt 456');
        $company->setGln('1234567890123');
        $company->setCorrespondenceCountryCode('PL');
        $company->setCorrespondenceAddressLine1('Correspondence Street 789');
        $company->setCorrespondenceAddressLine2('Suite 101');
        $company->setCorrespondenceGln('9876543210987');
        $company->setEmail('test@company.com');
        $company->setPhoneNumber('+48123456789');
        $company->setTaxpayerStatus(1);
        $company->setJstMarker(1);
        $company->setGvMarker(1);
        $company->setRole(1);
        $company->setOtherRoleMarker(true);
        $company->setRoleDescription('Primary contractor');
        $company->setSharePercentage(75.50);

        $this->assertEquals('Test Company Ltd.', $company->getName());
        $this->assertEquals('1234567890', $company->getTaxId());
        $this->assertEquals('PL', $company->getTaxpayerPrefix());
        $this->assertEquals('PL123456789012345', $company->getEoriNumber());
        $this->assertEquals('PL', $company->getEuCountryCode());
        $this->assertEquals('PL1234567890', $company->getVatRegNumberEu());
        $this->assertEquals('US', $company->getOtherIdCountryCode());
        $this->assertEquals('US123456789', $company->getOtherIdNumber());
        $this->assertFalse($company->getNoIdMarker());
        $this->assertEquals('INT123', $company->getInternalId());
        $this->assertEquals('BUY123', $company->getBuyerId());
        $this->assertEquals('CLI123', $company->getClientNumber());
        $this->assertEquals('PL', $company->getCountryCode());
        $this->assertEquals('Test Street 123', $company->getAddressLine1());
        $this->assertEquals('Apt 456', $company->getAddressLine2());
        $this->assertEquals('1234567890123', $company->getGln());
        $this->assertEquals('PL', $company->getCorrespondenceCountryCode());
        $this->assertEquals('Correspondence Street 789', $company->getCorrespondenceAddressLine1());
        $this->assertEquals('Suite 101', $company->getCorrespondenceAddressLine2());
        $this->assertEquals('9876543210987', $company->getCorrespondenceGln());
        $this->assertEquals('test@company.com', $company->getEmail());
        $this->assertEquals('+48123456789', $company->getPhoneNumber());
        $this->assertEquals(1, $company->getTaxpayerStatus());
        $this->assertEquals(1, $company->getJstMarker());
        $this->assertEquals(1, $company->getGvMarker());
        $this->assertEquals(1, $company->getRole());
        $this->assertTrue($company->getOtherRoleMarker());
        $this->assertEquals('Primary contractor', $company->getRoleDescription());
        $this->assertEquals(75.50, $company->getSharePercentage());
    }

    public function testCompanySoftDelete(): void
    {
        $company = new Company('Test Company');
        
        $this->assertTrue($company->isActive());
        $this->assertFalse($company->isDeleted());
        $this->assertNull($company->getDeletedAt());

        $company->softDelete();

        $this->assertFalse($company->isActive());
        $this->assertTrue($company->isDeleted());
        $this->assertInstanceOf(\DateTimeInterface::class, $company->getDeletedAt());
    }

    public function testCompanyRestore(): void
    {
        $company = new Company('Test Company');
        $company->softDelete();
        
        $this->assertTrue($company->isDeleted());

        $company->restore();

        $this->assertTrue($company->isActive());
        $this->assertFalse($company->isDeleted());
        $this->assertNull($company->getDeletedAt());
    }

    public function testCompanyUpdatedAtIsModifiedOnFieldChange(): void
    {
        $company = new Company('Test Company');
        $originalUpdatedAt = $company->getUpdatedAt();
        
        // Wait a moment to ensure different timestamp
        sleep(1);
        
        $company->setName('Updated Company');
        
        $this->assertNotEquals($originalUpdatedAt, $company->getUpdatedAt());
        $this->assertEquals('Updated Company', $company->getName());
    }
}