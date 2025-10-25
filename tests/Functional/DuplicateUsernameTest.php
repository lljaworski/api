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
        
        // Should return 422 or 409 with a meaningful error message
        $statusCode = $this->client->getResponse()->getStatusCode();
        $responseContent = $this->client->getResponse()->getContent();
        
        echo "\nStatus code: $statusCode\n";
        echo "Response: $responseContent\n\n";
        
        // For now, just check what the actual behavior is
        $this->assertNotEquals(Response::HTTP_CREATED, $statusCode, 'Should not create duplicate user');
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
