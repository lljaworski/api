<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Functional\Service;

use LukaszJaworski\CeidgBundle\Exception\CeidgApiException;
use LukaszJaworski\CeidgBundle\Model\FirmaCeidgDTO;
use LukaszJaworski\CeidgBundle\Service\CeidgApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;

class CeidgApiServiceTest extends TestCase
{
    private CeidgApiService $ceidgApiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create service with real HTTP client and env variables
        $httpClient = HttpClient::create();
        $apiUrl = $_ENV['CEIDG_API_URL'] ?? 'https://dane.biznes.gov.pl/api/ceidg/v3/firmy';
        $apiKey = $_ENV['CEIDG_API_KEY'] ?? '';
        
        $this->ceidgApiService = new CeidgApiService($httpClient, $apiUrl, $apiKey);
    }

    public function testFindByNipReturnsCompanyDataForValidNip(): void
    {
        // RED: This test will fail because service doesn't exist yet
        $nip = '6292346813';
        
        $result = $this->ceidgApiService->findByNip($nip);
        
        $this->assertInstanceOf(FirmaCeidgDTO::class, $result);
        $this->assertSame($nip, $result->nip);
        $this->assertNotEmpty($result->nazwa);
        $this->assertInstanceOf(\DateTimeInterface::class, $result->dataRozpoczeciaDzialalnosci);
    }

    public function testFindByNipReturnsNullForNonExistentNip(): void
    {
        // Use invalid NIP format which API should reject with 400 error
        // We'll catch that and treat as not found
        $nonExistentNip = '0000000001'; // Invalid NIP that doesn't exist
        
        $result = $this->ceidgApiService->findByNip($nonExistentNip);
        
        // Should return null when company not found or invalid NIP
        $this->assertNull($result);
    }

    public function testFindByNipThrowsExceptionOnApiError(): void
    {
        $this->expectException(CeidgApiException::class);
        $this->expectExceptionMessage('CEIDG API error');
        
        // Use invalid API key to trigger 401 error
        $httpClient = HttpClient::create();
        $invalidService = new CeidgApiService(
            $httpClient,
            'https://dane.biznes.gov.pl/api/ceidg/v3/firmy',
            'invalid_api_key'
        );
        
        $invalidService->findByNip('6292346813');
    }

    public function testFindByNipHandlesInvalidNipFormat(): void
    {
        // Test with completely invalid format
        $invalidNip = 'abc1234567';
        
        try {
            $result = $this->ceidgApiService->findByNip($invalidNip);
            // If no exception, result should be null
            $this->assertNull($result);
        } catch (CeidgApiException $e) {
            // API might return 400 error for invalid format, which is also acceptable
            $this->assertStringContainsString('CEIDG API error', $e->getMessage());
        }
    }
}
