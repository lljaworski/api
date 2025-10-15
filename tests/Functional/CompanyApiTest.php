<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyApiTest extends WebTestCase
{
    use DatabaseTestTrait;

    private $client;
    private ?string $adminToken = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testGetCompaniesRequiresAuthentication(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/companies');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testGetCompaniesRequiresAdminRole(): void
    {
        // This test will fail until we implement company endpoints
        // Get auth token for regular user (not admin)
        $regularUserToken = $this->getAuthToken('regularuser', 'password123');
        
        $this->client->request(Request::METHOD_GET, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
        ]);
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testGetCompaniesAsAdmin(): void
    {
        $adminToken = $this->getAuthToken();
        
        $this->client->request(Request::METHOD_GET, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testCreateCompanyAsAdmin(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123!');
        
        $companyData = [
            'name' => 'Test Company Ltd.',
            'taxId' => '1234567890',
            'email' => 'test@company.com',
            'phoneNumber' => '+48123456789'
        ];
        
        $this->client->request(Request::METHOD_POST, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($companyData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Company Ltd.', $responseData['name']);
        $this->assertEquals('1234567890', $responseData['taxId']);
        $this->assertEquals('test@company.com', $responseData['email']);
    }

    public function testGetSingleCompanyAsAdmin(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123!');
        
        // First create a company
        $companyData = ['name' => 'Test Company for Get'];
        $this->client->request(Request::METHOD_POST, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($companyData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $companyId = $createResponse['id'];
        
        // Now get the company
        $this->client->request(Request::METHOD_GET, "/api/companies/{$companyId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Company for Get', $responseData['name']);
    }

    public function testUpdateCompanyAsAdmin(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123!');
        
        // First create a company
        $companyData = ['name' => 'Original Company Name'];
        $this->client->request(Request::METHOD_POST, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($companyData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $companyId = $createResponse['id'];
        
        // Update the company
        $updateData = ['name' => 'Updated Company Name'];
        $this->client->request(Request::METHOD_PATCH, "/api/companies/{$companyId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode($updateData));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Company Name', $responseData['name']);
    }

    public function testDeleteCompanyAsAdmin(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123!');
        
        // First create a company
        $companyData = ['name' => 'Company to Delete'];
        $this->client->request(Request::METHOD_POST, '/api/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($companyData));
        
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $companyId = $createResponse['id'];
        
        // Delete the company
        $this->client->request(Request::METHOD_DELETE, "/api/companies/{$companyId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
        
        // Verify company is not found (soft deleted)
        $this->client->request(Request::METHOD_GET, "/api/companies/{$companyId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testSearchCompanies(): void
    {
        $adminToken = $this->getAuthToken('admin', 'admin123!');
        
        // Create some companies with different data
        $companies = [
            ['name' => 'Apple Inc.', 'email' => 'contact@apple.com'],
            ['name' => 'Microsoft Corp.', 'taxId' => '123456789'],
            ['name' => 'Google LLC', 'phoneNumber' => '+1234567890']
        ];
        
        foreach ($companies as $companyData) {
            $this->client->request(Request::METHOD_POST, '/api/companies', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
                'CONTENT_TYPE' => 'application/json',
            ], json_encode($companyData));
        }
        
        // Test search by name
        $this->client->request(Request::METHOD_GET, '/api/companies?name=Apple', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        // For API Platform collections, the response should contain companies
        $this->assertGreaterThan(0, $responseData['hydra:totalItems'] ?? 0);
    }

    private function getAuthToken(string $username = 'admin', string $password = 'admin123!'): string
    {
        if ($this->adminToken !== null && $username === 'admin') {
            return $this->adminToken;
        }
        
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => $password
        ]));
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        if ($username === 'admin') {
            $this->adminToken = $response['token'];
        }
        
        return $response['token'];
    }
}