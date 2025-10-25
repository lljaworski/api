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

    public function testCreateUserWithDuplicateUsername(): void
    {
        $username = $this->generateUniqueUsername('dupuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        // Create first user
        $this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->createdEntities[] = $user;
        
        // Try to create duplicate user
        $this->requestAsAdmin(Request::METHOD_POST, '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $statusCode = $this->client->getResponse()->getStatusCode();
        $responseContent = $this->client->getResponse()->getContent();
        
        echo "\nStatus Code: $statusCode\n";
        echo "Response: $responseContent\n";
        
        // Should return 422 Unprocessable Entity with a meaningful error message
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $statusCode);
        
        $responseData = json_decode($responseContent, true);
        
        // Check for violations array (API Platform's standard validation error format)
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertNotEmpty($responseData['violations']);
        
        // Check that the error message mentions the username is already taken
        $violationMessages = array_column($responseData['violations'], 'message');
        $hasUsernameError = false;
        foreach ($violationMessages as $message) {
            if (stripos($message, 'username') !== false && 
                (stripos($message, 'taken') !== false || 
                 stripos($message, 'exists') !== false || 
                 stripos($message, 'already') !== false)) {
                $hasUsernameError = true;
                break;
            }
        }
        
        $this->assertTrue($hasUsernameError, 'Error message should indicate username is already taken');
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
