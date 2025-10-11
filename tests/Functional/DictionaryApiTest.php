<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Trait\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DictionaryApiTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin exists for authentication tests
        $this->ensureTestAdmin();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testDictionaryEndpointRequiresAuthentication(): void
    {
        // Test unauthenticated access should fail
        $this->client->request(Request::METHOD_GET, '/api/dictionaries/roles');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testRolesDictionaryRequiresAdminRole(): void
    {
        // Create regular user for testing role-based access
        $username = $this->generateUniqueUsername('regularuser');
        $this->createTestUser($username, 'password123', ['ROLE_USER']);
        
        // Get token for regular user
        $token = $this->getAuthToken($username, 'password123');
        
        // Regular user should not have access to roles dictionary
        $this->client->request(Request::METHOD_GET, '/api/dictionaries/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanAccessRolesDictionary(): void
    {
        // Get admin token
        $token = $this->getAuthToken('admin', 'admin');
        
        // Admin should have access to roles dictionary
        $this->client->request(Request::METHOD_GET, '/api/dictionaries/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Verify response structure
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('type', $responseData);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertEquals('roles', $responseData['type']);
        
        // Verify items structure
        $this->assertIsArray($responseData['items']);
        $this->assertNotEmpty($responseData['items']);
        
        // Verify each item has id and name
        foreach ($responseData['items'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertIsString($item['id']);
            $this->assertIsString($item['name']);
        }
        
        // Verify specific roles from RolesEnum
        $itemsById = array_column($responseData['items'], 'name', 'id');
        $this->assertArrayHasKey('ROLE_ADMIN', $itemsById);
        $this->assertArrayHasKey('ROLE_USER', $itemsById);
        $this->assertEquals('Administrator', $itemsById['ROLE_ADMIN']);
        $this->assertEquals('User', $itemsById['ROLE_USER']);
    }

    public function testNonExistentDictionaryReturns404(): void
    {
        // Get admin token
        $token = $this->getAuthToken('admin', 'admin');
        
        // Request non-existent dictionary
        $this->client->request(Request::METHOD_GET, '/api/dictionaries/nonexistent', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDictionaryEndpointAcceptsOnlyValidTypeFormat(): void
    {
        // Get admin token
        $token = $this->getAuthToken('admin', 'admin');
        
        // Test invalid type format (with special characters that should be rejected by regex)
        $this->client->request(Request::METHOD_GET, '/api/dictionaries/invalid-type-with-dashes', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);
        
        // Should return 404 due to route constraint mismatch
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    private function getAuthToken(string $username = 'admin', string $password = 'admin'): string
    {
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => $password
        ]));
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        return $response['token'];
    }
}