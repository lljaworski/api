<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest extends WebTestCase
{
    use DatabaseTestTrait;
    
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure test admin user exists
        $this->ensureTestAdmin();
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
        
        // Verify JWT token structure (header.payload.signature)
        $tokenParts = explode('.', $data['token']);
        $this->assertCount(3, $tokenParts, 'JWT token should have 3 parts');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'wrongpassword'
        ]));

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals(401, $data['code']);
        $this->assertEquals('Invalid credentials.', $data['message']);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'nonexistent',
            'password' => 'password'
        ]));

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $data['code']);
        $this->assertEquals('Invalid credentials.', $data['message']);
    }

    public function testLoginWithMissingCredentials(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testProtectedEndpointWithValidToken(): void
    {
        // First, get a valid token
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));

        $loginResponse = $this->client->getResponse();
        $loginData = json_decode($loginResponse->getContent(), true);
        $token = $loginData['token'];

        // Now test the protected endpoint
        $this->client->request('GET', '/api/protected', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('roles', $data);
        
        $this->assertEquals('admin', $data['user']);
        $this->assertContains('ROLE_ADMIN', $data['roles']);
        $this->assertContains('ROLE_USER', $data['roles']);
        $this->assertEquals('This is a protected endpoint - you are authenticated!', $data['message']);
    }

    public function testProtectedEndpointWithoutToken(): void
    {
        $this->client->request('GET', '/api/protected');

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testProtectedEndpointWithInvalidToken(): void
    {
        $this->client->request('GET', '/api/protected', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testProtectedEndpointWithMalformedAuthHeader(): void
    {
        $this->client->request('GET', '/api/protected', [], [], [
            'HTTP_AUTHORIZATION' => 'InvalidHeader',
        ]);

        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testHealthEndpointRemainsPublic(): void
    {
        $this->client->request('GET', '/api/health');

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
    }

    public function testPingEndpointRemainsPublic(): void
    {
        $this->client->request('GET', '/api/ping');

        $response = $this->client->getResponse();
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
    }

    public function testTokenExpirationTime(): void
    {
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'admin',
            'password' => 'admin123!'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $token = $data['token'];

        // Decode JWT payload to check expiration
        $tokenParts = explode('.', $token);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('username', $payload);
        $this->assertArrayHasKey('roles', $payload);
        
        $this->assertEquals('admin', $payload['username']);
        $this->assertContains('ROLE_ADMIN', $payload['roles']);
        
        // Check that expiration is in the future
        $this->assertGreaterThan(time(), $payload['exp']);
        // Check that issued at is not in the future
        $this->assertLessThanOrEqual(time(), $payload['iat']);
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