<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Unit\Model;

use LukaszJaworski\CeidgBundle\Model\AdresDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AdresDTO.
 */
class AdresDTOTest extends TestCase
{
    public function testFromApiResponseWithFullData(): void
    {
        $apiData = [
            'ulica' => 'Marszałkowska',
            'budynek' => '1',
            'lokal' => '2',
            'miasto' => 'Warszawa',
            'wojewodztwo' => 'mazowieckie',
            'powiat' => 'Warszawa',
            'gmina' => 'Warszawa',
            'kraj' => 'Polska',
            'kod' => '00-001',
            'skrytkaPocztowa' => null,
            'opisNietypowegoMiejsca' => null,
            'adresat' => 'Jan Kowalski',
            'terc' => '1465011',
            'simc' => '0918123',
            'ulic' => '08874',
        ];

        $adres = AdresDTO::fromApiResponse($apiData);

        $this->assertNotNull($adres);
        $this->assertEquals('Marszałkowska', $adres->ulica);
        $this->assertEquals('1', $adres->budynek);
        $this->assertEquals('2', $adres->lokal);
        $this->assertEquals('Warszawa', $adres->miasto);
        $this->assertEquals('mazowieckie', $adres->wojewodztwo);
        $this->assertEquals('00-001', $adres->kod);
        $this->assertEquals('Jan Kowalski', $adres->adresat);
    }

    public function testFromApiResponseWithPartialData(): void
    {
        $apiData = [
            'miasto' => 'Kraków',
            'kod' => '30-001',
        ];

        $adres = AdresDTO::fromApiResponse($apiData);

        $this->assertNotNull($adres);
        $this->assertEquals('Kraków', $adres->miasto);
        $this->assertEquals('30-001', $adres->kod);
        $this->assertNull($adres->ulica);
        $this->assertNull($adres->budynek);
    }

    public function testFromApiResponseWithNullData(): void
    {
        $adres = AdresDTO::fromApiResponse(null);

        $this->assertNull($adres);
    }

    public function testFromApiResponseWithEmptyArray(): void
    {
        $adres = AdresDTO::fromApiResponse([]);

        $this->assertNull($adres);
    }

    public function testToArrayContainsAllFields(): void
    {
        $adres = new AdresDTO(
            ulica: 'Główna',
            budynek: '10',
            lokal: '5',
            miasto: 'Poznań',
            wojewodztwo: 'wielkopolskie',
            powiat: 'Poznań',
            gmina: 'Poznań',
            kraj: 'Polska',
            kod: '60-001',
            skrytkaPocztowa: null,
            opisNietypowegoMiejsca: null,
            adresat: 'Test User',
            terc: '3064011',
            simc: '0874569',
            ulic: '12345',
        );

        $array = $adres->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Główna', $array['ulica']);
        $this->assertEquals('10', $array['budynek']);
        $this->assertEquals('5', $array['lokal']);
        $this->assertEquals('Poznań', $array['miasto']);
        $this->assertEquals('wielkopolskie', $array['wojewodztwo']);
        $this->assertEquals('60-001', $array['kod']);
        $this->assertArrayHasKey('skrytkaPocztowa', $array);
        $this->assertNull($array['skrytkaPocztowa']);
    }

    public function testGetFormattedAddressWithFullStreetAddress(): void
    {
        $adres = new AdresDTO(
            ulica: 'Marszałkowska',
            budynek: '1',
            lokal: '2',
            miasto: 'Warszawa',
            kod: '00-001',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('ul. Marszałkowska 1/2, 00-001 Warszawa', $formatted);
    }

    public function testGetFormattedAddressWithoutApartment(): void
    {
        $adres = new AdresDTO(
            ulica: 'Długa',
            budynek: '15',
            miasto: 'Gdańsk',
            kod: '80-001',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('ul. Długa 15, 80-001 Gdańsk', $formatted);
    }

    public function testGetFormattedAddressWithoutStreet(): void
    {
        $adres = new AdresDTO(
            budynek: '5',
            lokal: '3',
            miasto: 'Wrocław',
            kod: '50-001',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('5/3, 50-001 Wrocław', $formatted);
    }

    public function testGetFormattedAddressWithOnlyCity(): void
    {
        $adres = new AdresDTO(
            miasto: 'Łódź',
            kod: '90-001',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('90-001 Łódź', $formatted);
    }

    public function testGetFormattedAddressWithNonStandardLocation(): void
    {
        $adres = new AdresDTO(
            miasto: 'Szczecin',
            kod: '70-001',
            opisNietypowegoMiejsca: 'Przy stacji benzynowej',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('70-001 Szczecin, Przy stacji benzynowej', $formatted);
    }

    public function testGetFormattedAddressWithMinimalData(): void
    {
        $adres = new AdresDTO(
            miasto: 'Katowice',
        );

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('Katowice', $formatted);
    }

    public function testGetFormattedAddressWithEmptyData(): void
    {
        $adres = new AdresDTO();

        $formatted = $adres->getFormattedAddress();

        $this->assertEquals('', $formatted);
    }
}
