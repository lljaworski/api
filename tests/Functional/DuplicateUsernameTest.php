<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Tests\Trait\DatabaseTestTrait;
use App\Tests\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for duplicate username validation.
 * 
 * This test ensures that when a user attempts to create a new user with a username
 * that already exists in the database, the API returns a proper validation error
 * (422 Unprocessable Entity) with a clear message, instead of a database exception (500).
 */
class DuplicateUsernameTest extends WebTestCase
{
    use DatabaseTestTrait;
    use RequestTrait;
    
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin user exists for testing
        $this->ensureTestAdmin();
    }

    /**
     * Test that creating a user with a duplicate username returns a proper validation error.
     * 
     * Expected behavior:
     * - First user creation succeeds (201 Created)
     * - Second user creation with same username fails (422 Unprocessable Entity)
     * - Error response contains a clear message about the username being taken
     * - Error follows API Platform's standard ConstraintViolation format
     */
    public function testCreateUserWithDuplicateUsername(): void
    {
        $username = $this->generateUniqueUsername('dupuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        // Create first user - should succeed
        $this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->createdEntities[] = $user;
        
        // Try to create duplicate user - should fail with validation error
        $this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $statusCode = $this->client->getResponse()->getStatusCode();
        $responseContent = $this->client->getResponse()->getContent();
        
        // Should return 422 Unprocessable Entity (not 500 Internal Server Error)
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $statusCode);
        
        $responseData = json_decode($responseContent, true);
        
        // Check for violations array (API Platform's standard validation error format)
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertNotEmpty($responseData['violations']);
        
        // Verify the error is about username
        $this->assertArrayHasKey('propertyPath', $responseData['violations'][0]);
        $this->assertEquals('username', $responseData['violations'][0]['propertyPath']);
        
        // Verify the exact error message
        $this->assertEquals('This username is already taken.', $responseData['violations'][0]['message']);
        
        // Verify response has correct structure
        $this->assertArrayHasKey('@type', $responseData);
        $this->assertEquals('ConstraintViolation', $responseData['@type']);
        $this->assertEquals(422, $responseData['status']);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        
        // Clean up entity manager
        $this->entityManager->close();
        
        parent::tearDown();
    }
}
