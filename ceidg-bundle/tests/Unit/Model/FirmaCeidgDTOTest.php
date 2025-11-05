<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Unit\Model;

use LukaszJaworski\CeidgBundle\Model\AdresDTO;
use LukaszJaworski\CeidgBundle\Model\FirmaCeidgDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FirmaCeidgDTO including contact information fields.
 */
class FirmaCeidgDTOTest extends TestCase
{
    public function testFromApiResponseWithFullContactData(): void
    {
        $apiData = [
            'wlasciciel' => ['nip' => '1234567890'],
            'nazwa' => 'Test Company',
            'dataRozpoczecia' => '2023-01-01',
            'status' => 'Aktywny',
            'telefon' => '123456789',
            'email' => 'test@example.com',
            'www' => 'https://example.com',
            'adresDoreczenElektronicznych' => 'elektroniczny@example.com',
            'innaFormaKonaktu' => 'Skype: testcompany',
            'adresDzialalnosci' => [
                'ulica' => 'Testowa',
                'budynek' => '1',
                'miasto' => 'Warszawa',
                'kod' => '00-001',
            ],
        ];

        $firma = FirmaCeidgDTO::fromApiResponse($apiData);

        $this->assertEquals('1234567890', $firma->nip);
        $this->assertEquals('Test Company', $firma->nazwa);
        $this->assertEquals('123456789', $firma->telefon);
        $this->assertEquals('test@example.com', $firma->email);
        $this->assertEquals('https://example.com', $firma->www);
        $this->assertEquals('elektroniczny@example.com', $firma->adresDoreczenElektronicznych);
        $this->assertEquals('Skype: testcompany', $firma->innaFormaKonaktu);
    }

    public function testFromApiResponseWithPartialContactData(): void
    {
        $apiData = [
            'wlasciciel' => ['nip' => '9876543210'],
            'nazwa' => 'Partial Company',
            'dataRozpoczecia' => '2023-01-01',
            'email' => 'partial@example.com',
            'telefon' => '987654321',
            // www, adresDoreczenElektronicznych, innaFormaKonaktu missing
        ];

        $firma = FirmaCeidgDTO::fromApiResponse($apiData);

        $this->assertEquals('9876543210', $firma->nip);
        $this->assertEquals('Partial Company', $firma->nazwa);
        $this->assertEquals('partial@example.com', $firma->email);
        $this->assertEquals('987654321', $firma->telefon);
        $this->assertNull($firma->www);
        $this->assertNull($firma->adresDoreczenElektronicznych);
        $this->assertNull($firma->innaFormaKonaktu);
    }

    public function testFromApiResponseWithNoContactData(): void
    {
        $apiData = [
            'wlasciciel' => ['nip' => '5555555555'],
            'nazwa' => 'No Contact Company',
            'dataRozpoczecia' => '2023-01-01',
        ];

        $firma = FirmaCeidgDTO::fromApiResponse($apiData);

        $this->assertEquals('5555555555', $firma->nip);
        $this->assertEquals('No Contact Company', $firma->nazwa);
        $this->assertNull($firma->telefon);
        $this->assertNull($firma->email);
        $this->assertNull($firma->www);
        $this->assertNull($firma->adresDoreczenElektronicznych);
        $this->assertNull($firma->innaFormaKonaktu);
    }

    public function testToArrayIncludesContactFields(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');
        
        $firma = new FirmaCeidgDTO(
            nip: '1111111111',
            nazwa: 'Array Test Company',
            dataRozpoczeciaDzialalnosci: $date,
            dataPowstania: $date,
            status: 'Aktywny',
            telefon: '111222333',
            email: 'array@test.com',
            www: 'https://arraytest.com',
            adresDoreczenElektronicznych: 'elektroniczny@arraytest.com',
            innaFormaKonaktu: 'Signal: arraytest'
        );

        $array = $firma->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('1111111111', $array['nip']);
        $this->assertEquals('Array Test Company', $array['nazwa']);
        $this->assertEquals('111222333', $array['telefon']);
        $this->assertEquals('array@test.com', $array['email']);
        $this->assertEquals('https://arraytest.com', $array['www']);
        $this->assertEquals('elektroniczny@arraytest.com', $array['adresDoreczenElektronicznych']);
        $this->assertEquals('Signal: arraytest', $array['innaFormaKonaktu']);
    }

    public function testToArrayWithNullContactFields(): void
    {
        $date = new \DateTimeImmutable('2023-01-01');
        
        $firma = new FirmaCeidgDTO(
            nip: '2222222222',
            nazwa: 'Null Contact Company',
            dataRozpoczeciaDzialalnosci: $date,
            dataPowstania: $date,
            telefon: null,
            email: null,
            www: null,
            adresDoreczenElektronicznych: null,
            innaFormaKonaktu: null
        );

        $array = $firma->toArray();

        $this->assertArrayHasKey('telefon', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('www', $array);
        $this->assertArrayHasKey('adresDoreczenElektronicznych', $array);
        $this->assertArrayHasKey('innaFormaKonaktu', $array);
        
        $this->assertNull($array['telefon']);
        $this->assertNull($array['email']);
        $this->assertNull($array['www']);
        $this->assertNull($array['adresDoreczenElektronicznych']);
        $this->assertNull($array['innaFormaKonaktu']);
    }

    public function testConstructorWithAllContactFields(): void
    {
        $date = new \DateTimeImmutable('2023-05-15');
        
        $firma = new FirmaCeidgDTO(
            nip: '3333333333',
            nazwa: 'Constructor Test',
            dataRozpoczeciaDzialalnosci: $date,
            dataPowstania: $date,
            status: 'Zawieszony',
            telefon: '333444555',
            email: 'constructor@test.com',
            www: 'https://constructor.test',
            adresDoreczenElektronicznych: 'e-doreczenia@constructor.test',
            innaFormaKonaktu: 'WhatsApp: +48333444555'
        );

        $this->assertEquals('3333333333', $firma->nip);
        $this->assertEquals('Constructor Test', $firma->nazwa);
        $this->assertEquals($date, $firma->dataRozpoczeciaDzialalnosci);
        $this->assertEquals($date, $firma->dataPowstania);
        $this->assertEquals('Zawieszony', $firma->status);
        $this->assertEquals('333444555', $firma->telefon);
        $this->assertEquals('constructor@test.com', $firma->email);
        $this->assertEquals('https://constructor.test', $firma->www);
        $this->assertEquals('e-doreczenia@constructor.test', $firma->adresDoreczenElektronicznych);
        $this->assertEquals('WhatsApp: +48333444555', $firma->innaFormaKonaktu);
    }

    public function testFromApiResponsePreservesExistingFunctionality(): void
    {
        // Test that existing functionality still works with contact fields added
        $apiData = [
            'wlasciciel' => ['nip' => '4444444444'],
            'nazwa' => 'Compatibility Test',
            'dataRozpoczecia' => '2023-06-01',
            'dataZawieszenia' => '2023-07-01',
            'dataWznowienia' => '2023-08-01',
            'dataZakonczenia' => '2023-09-01',
            'status' => 'Zakończony',
            'adresDzialalnosci' => [
                'ulica' => 'Compatibility',
                'budynek' => '99',
                'miasto' => 'Kraków',
                'kod' => '30-001',
            ],
            'adresKorespondencyjny' => [
                'ulica' => 'Korespondencyjna',
                'budynek' => '88',
                'miasto' => 'Wrocław',
                'kod' => '50-001',
            ],
            'adresyDzialalnosciDodatkowe' => [
                [
                    'ulica' => 'Dodatkowa',
                    'budynek' => '77',
                    'miasto' => 'Gdańsk',
                    'kod' => '80-001',
                ],
            ],
            // Including some contact fields
            'telefon' => '444555666',
            'email' => 'compatibility@test.com',
        ];

        $firma = FirmaCeidgDTO::fromApiResponse($apiData);

        // Test existing fields still work
        $this->assertEquals('4444444444', $firma->nip);
        $this->assertEquals('Compatibility Test', $firma->nazwa);
        $this->assertEquals('Zakończony', $firma->status);
        $this->assertEquals('2023-06-01', $firma->dataRozpoczeciaDzialalnosci->format('Y-m-d'));
        $this->assertEquals('2023-07-01', $firma->dataZawieszeniaDzialalnosci?->format('Y-m-d'));
        $this->assertEquals('2023-08-01', $firma->dataWznowieniaDzialalnosci?->format('Y-m-d'));
        $this->assertEquals('2023-09-01', $firma->dataZakonczeniaDzialalnosci?->format('Y-m-d'));
        
        // Test addresses still work
        $this->assertNotNull($firma->adresDzialalnosci);
        $this->assertEquals('Compatibility', $firma->adresDzialalnosci->ulica);
        $this->assertNotNull($firma->adresKorespondencyjny);
        $this->assertEquals('Korespondencyjna', $firma->adresKorespondencyjny->ulica);
        $this->assertCount(1, $firma->adresyDzialalnosciDodatkowe);
        
        // Test new contact fields work
        $this->assertEquals('444555666', $firma->telefon);
        $this->assertEquals('compatibility@test.com', $firma->email);
        $this->assertNull($firma->www);
        $this->assertNull($firma->adresDoreczenElektronicznych);
        $this->assertNull($firma->innaFormaKonaktu);
    }
}