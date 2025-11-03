<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for CEIDG Company endpoint.
 * 
 * Tests the complete flow from HTTP request to response including:
 * - Authentication and authorization
 * - NIP validation
 * - API integration
 * - Error handling
 */
class CeidgCompanyEndpointTest extends WebTestCase
{
    /**
     * Helper method to get authentication token for testing.
     * Creates a new client and returns both the client and token.
     */
    private function getAuthenticatedClient(): array
    {
        $client = static::createClient();
        
        $client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));
        
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        
        return ['client' => $client, 'token' => $response['token']];
    }

    public function testEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/1234567890');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testInvalidNipFormatReturns404(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        // Test with 9 digits (too short)
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/123456789', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testInvalidNipWithLettersReturns404(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/123ABC7890', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testValidNipFormatIsAccepted(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        // This will likely return 404 or 503 depending on CEIDG API availability
        // but should not return 400 for format issues
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/1234567890', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Should be either 404 (not found), 503 (service unavailable), or 200 (found)
        // but NOT 400 (bad request)
        $this->assertNotEquals(Response::HTTP_BAD_REQUEST, $statusCode);
        $this->assertContains(
            $statusCode,
            [Response::HTTP_OK, Response::HTTP_NOT_FOUND, Response::HTTP_SERVICE_UNAVAILABLE]
        );
    }

    public function testEndpointReturnsJsonFormat(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/1234567890', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $response = $client->getResponse();
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json') ||
            $response->headers->contains('Content-Type', 'application/ld+json') ||
            str_contains($response->headers->get('Content-Type') ?? '', 'json')
        );
    }

    public function testResponseStructureContainsAddressFields(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/1234567890', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Only check structure if company was found (200) or if it's a service error
        if ($statusCode === Response::HTTP_OK) {
            $responseData = json_decode($client->getResponse()->getContent(), true);
            
            // Verify address fields are present in response structure
            $this->assertArrayHasKey('adresDzialalnosci', $responseData);
            $this->assertArrayHasKey('adresKorespondencyjny', $responseData);
            $this->assertArrayHasKey('adresyDzialalnosciDodatkowe', $responseData);
            
            // Address fields can be null or arrays
            if ($responseData['adresDzialalnosci'] !== null) {
                $this->assertIsArray($responseData['adresDzialalnosci']);
            }
            
            if ($responseData['adresKorespondencyjny'] !== null) {
                $this->assertIsArray($responseData['adresKorespondencyjny']);
            }
            
            $this->assertIsArray($responseData['adresyDzialalnosciDodatkowe']);
        } else {
            // For 404 or 503, just verify the status
            $this->assertContains($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_SERVICE_UNAVAILABLE]);
        }
    }

    public function testRoleUserCanAccessEndpoint(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        // Admin token includes ROLE_USER via role hierarchy
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/1234567890', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $statusCode = $client->getResponse()->getStatusCode();
        
        // Should not return 403 Forbidden
        $this->assertNotEquals(Response::HTTP_FORBIDDEN, $statusCode);
    }

    public function testNipWithOnlyNineDigitsIsRejected(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/999999999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testNipWithElevenDigitsIsRejected(): void
    {
        ['client' => $client, 'token' => $token] = $this->getAuthenticatedClient();
        
        $client->request(Request::METHOD_GET, '/api/ceidg/companies/99999999999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}

