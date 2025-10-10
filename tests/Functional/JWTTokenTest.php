<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Trait\DatabaseTestTrait;

class JWTTokenTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin user exists for testing
        $this->ensureTestAdmin();
    }

    public function testJWTTokenContainsUserId(): void
    {
        // Authenticate to get JWT token
        $this->client->request(Request::METHOD_POST, '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        
        // Decode JWT token to verify userId is included
        $token = $responseData['token'];
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT token should have 3 parts');
        
        // Decode the payload (second part)
        $payload = json_decode(base64_decode($parts[1]), true);
        
        // Verify userId is present in the payload
        $this->assertArrayHasKey('userId', $payload, 'JWT token should contain userId field');
        $this->assertIsInt($payload['userId'], 'userId should be an integer');
        $this->assertGreaterThan(0, $payload['userId'], 'userId should be a positive integer');
        
        // Verify token contains standard claims
        $this->assertArrayHasKey('username', $payload, 'JWT token should contain username');
        $this->assertArrayHasKey('roles', $payload, 'JWT token should contain roles');
        $this->assertArrayHasKey('iat', $payload, 'JWT token should contain issued at timestamp');
        $this->assertArrayHasKey('exp', $payload, 'JWT token should contain expiration timestamp');
        
        // Verify admin user data
        $this->assertEquals('admin', $payload['username']);
        $this->assertContains('ROLE_ADMIN', $payload['roles']);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }
}