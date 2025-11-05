<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Unit\ApiResource;

use LukaszJaworski\CeidgBundle\ApiResource\CeidgCompany;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CeidgCompany API Resource including contact information fields.
 */
class CeidgCompanyTest extends TestCase
{
    public function testConstructorWithContactFields(): void
    {
        $company = new CeidgCompany(
            nip: '1234567890',
            nazwa: 'Test Company',
            dataRozpoczeciaDzialalnosci: '2023-01-01',
            dataPowstania: '2023-01-01',
            status: 'Aktywny',
            telefon: '123456789',
            email: 'test@example.com',
            www: 'https://example.com',
            adresDoreczenElektronicznych: 'elektroniczny@example.com',
            innaFormaKonaktu: 'Skype: testcompany'
        );

        $this->assertEquals('1234567890', $company->nip);
        $this->assertEquals('Test Company', $company->nazwa);
        $this->assertEquals('2023-01-01', $company->dataRozpoczeciaDzialalnosci);
        $this->assertEquals('2023-01-01', $company->dataPowstania);
        $this->assertEquals('Aktywny', $company->status);
        $this->assertEquals('123456789', $company->telefon);
        $this->assertEquals('test@example.com', $company->email);
        $this->assertEquals('https://example.com', $company->www);
        $this->assertEquals('elektroniczny@example.com', $company->adresDoreczenElektronicznych);
        $this->assertEquals('Skype: testcompany', $company->innaFormaKonaktu);
    }

    public function testConstructorWithNullContactFields(): void
    {
        $company = new CeidgCompany(
            nip: '9876543210',
            nazwa: 'Minimal Company',
            dataRozpoczeciaDzialalnosci: '2023-01-01',
            dataPowstania: '2023-01-01',
            telefon: null,
            email: null,
            www: null,
            adresDoreczenElektronicznych: null,
            innaFormaKonaktu: null
        );

        $this->assertEquals('9876543210', $company->nip);
        $this->assertEquals('Minimal Company', $company->nazwa);
        $this->assertNull($company->telefon);
        $this->assertNull($company->email);
        $this->assertNull($company->www);
        $this->assertNull($company->adresDoreczenElektronicznych);
        $this->assertNull($company->innaFormaKonaktu);
    }

    public function testConstructorWithMixedContactFields(): void
    {
        $company = new CeidgCompany(
            nip: '5555555555',
            nazwa: 'Mixed Company',
            dataRozpoczeciaDzialalnosci: '2023-06-01',
            dataPowstania: '2023-06-01',
            telefon: '555666777',
            email: 'mixed@example.com',
            www: null, // No website
            adresDoreczenElektronicznych: null, // No electronic delivery
            innaFormaKonaktu: 'Signal: mixed555'
        );

        $this->assertEquals('5555555555', $company->nip);
        $this->assertEquals('Mixed Company', $company->nazwa);
        $this->assertEquals('555666777', $company->telefon);
        $this->assertEquals('mixed@example.com', $company->email);
        $this->assertNull($company->www);
        $this->assertNull($company->adresDoreczenElektronicznych);
        $this->assertEquals('Signal: mixed555', $company->innaFormaKonaktu);
    }

    public function testConstructorPreservesExistingFunctionality(): void
    {
        // Test that existing address functionality still works
        $adresDzialalnosci = [
            'ulica' => 'Testowa',
            'budynek' => '1',
            'miasto' => 'Warszawa',
            'kod' => '00-001',
        ];

        $adresKorespondencyjny = [
            'ulica' => 'Korespondencyjna',
            'budynek' => '2',
            'miasto' => 'Kraków',
            'kod' => '30-001',
        ];

        $adresyDodatkowe = [
            [
                'ulica' => 'Dodatkowa',
                'budynek' => '3',
                'miasto' => 'Gdańsk',
                'kod' => '80-001',
            ],
        ];

        $company = new CeidgCompany(
            nip: '7777777777',
            nazwa: 'Full Featured Company',
            dataRozpoczeciaDzialalnosci: '2023-01-01',
            dataPowstania: '2023-01-01',
            status: 'Aktywny',
            dataZawieszeniaDzialalnosci: '2023-06-01',
            dataWznowieniaDzialalnosci: '2023-07-01',
            dataZakonczeniaDzialalnosci: null,
            adresDzialalnosci: $adresDzialalnosci,
            adresKorespondencyjny: $adresKorespondencyjny,
            adresyDzialalnosciDodatkowe: $adresyDodatkowe,
            telefon: '777888999',
            email: 'full@example.com',
            www: 'https://full.example.com',
            adresDoreczenElektronicznych: 'e-doreczenia@full.example.com',
            innaFormaKonaktu: 'WhatsApp: +48777888999'
        );

        // Test existing functionality
        $this->assertEquals('7777777777', $company->nip);
        $this->assertEquals('Full Featured Company', $company->nazwa);
        $this->assertEquals('Aktywny', $company->status);
        $this->assertEquals('2023-06-01', $company->dataZawieszeniaDzialalnosci);
        $this->assertEquals('2023-07-01', $company->dataWznowieniaDzialalnosci);
        $this->assertNull($company->dataZakonczeniaDzialalnosci);
        $this->assertEquals($adresDzialalnosci, $company->adresDzialalnosci);
        $this->assertEquals($adresKorespondencyjny, $company->adresKorespondencyjny);
        $this->assertEquals($adresyDodatkowe, $company->adresyDzialalnosciDodatkowe);

        // Test new contact functionality
        $this->assertEquals('777888999', $company->telefon);
        $this->assertEquals('full@example.com', $company->email);
        $this->assertEquals('https://full.example.com', $company->www);
        $this->assertEquals('e-doreczenia@full.example.com', $company->adresDoreczenElektronicznych);
        $this->assertEquals('WhatsApp: +48777888999', $company->innaFormaKonaktu);
    }

    public function testConstructorWithDefaultParameters(): void
    {
        // Test that constructor still works with minimal required parameters
        $company = new CeidgCompany(
            nip: '1111111111',
            nazwa: 'Minimal Required',
            dataRozpoczeciaDzialalnosci: '2023-01-01',
            dataPowstania: '2023-01-01'
        );

        $this->assertEquals('1111111111', $company->nip);
        $this->assertEquals('Minimal Required', $company->nazwa);
        $this->assertEquals('2023-01-01', $company->dataRozpoczeciaDzialalnosci);
        $this->assertEquals('2023-01-01', $company->dataPowstania);
        
        // All optional fields should be null by default
        $this->assertNull($company->status);
        $this->assertNull($company->dataZawieszeniaDzialalnosci);
        $this->assertNull($company->dataWznowieniaDzialalnosci);
        $this->assertNull($company->dataZakonczeniaDzialalnosci);
        $this->assertNull($company->adresDzialalnosci);
        $this->assertNull($company->adresKorespondencyjny);
        $this->assertEquals([], $company->adresyDzialalnosciDodatkowe);
        
        // New contact fields should also be null by default
        $this->assertNull($company->telefon);
        $this->assertNull($company->email);
        $this->assertNull($company->www);
        $this->assertNull($company->adresDoreczenElektronicznych);
        $this->assertNull($company->innaFormaKonaktu);
    }
}