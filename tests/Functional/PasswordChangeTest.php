<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Trait\DatabaseTestTrait;

class PasswordChangeTest extends WebTestCase
{
    use DatabaseTestTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function testChangePasswordAsAdminForOtherUser(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'oldpassword123!', ['ROLE_USER']);
        
        // Get admin auth token
        $adminToken = $this->getAuthToken('admin', 'admin');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'oldpassword123!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'newpassword456@'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Password changed successfully.', $responseData['message']);
    }

    public function testChangePasswordAsUserForOwnPassword(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'oldpassword123!', ['ROLE_USER']);
        
        // Get user auth token
        $userToken = $this->getAuthToken($testUsername, 'oldpassword123!');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'oldpassword123!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'newpassword456@'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Password changed successfully.', $responseData['message']);
    }

    public function testChangePasswordWithoutAuthentication(): void
    {
        $passwordChangeData = [
            'userId' => 1,
            'oldPassword' => 'oldpassword123!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'newpassword456@'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testUserCannotChangeOtherUsersPassword(): void
    {
        // Create two test users
        $user1Username = $this->generateUniqueUsername('user1');
        $user1 = $this->createTestUser($user1Username, 'password123!', ['ROLE_USER']);
        
        $user2Username = $this->generateUniqueUsername('user2');
        $user2 = $this->createTestUser($user2Username, 'password123!', ['ROLE_USER']);
        
        // Get user1 auth token
        $user1Token = $this->getAuthToken($user1Username, 'password123!');
        
        // Try to change user2's password with user1's token
        $passwordChangeData = [
            'userId' => $user2->getId(),
            'oldPassword' => 'password123!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'newpassword456@'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user1Token,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testChangePasswordWithIncorrectOldPassword(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'oldpassword123!', ['ROLE_USER']);
        
        // Get user auth token
        $userToken = $this->getAuthToken($testUsername, 'oldpassword123!');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'wrongoldpassword!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'newpassword456@'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testChangePasswordWithMismatchedConfirmation(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'oldpassword123!', ['ROLE_USER']);
        
        // Get user auth token
        $userToken = $this->getAuthToken($testUsername, 'oldpassword123!');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'oldpassword123!',
            'newPassword' => 'newpassword456@',
            'passwordConfirmation' => 'differentpassword789#'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
    }

    public function testChangePasswordWithWeakPassword(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'oldpassword123!', ['ROLE_USER']);
        
        // Get user auth token
        $userToken = $this->getAuthToken($testUsername, 'oldpassword123!');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'oldpassword123!',
            'newPassword' => 'weak', // Too short, no numbers, no special chars
            'passwordConfirmation' => 'weak'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
    }

    public function testChangePasswordWithSameAsOldPassword(): void
    {
        // Create a test user
        $testUsername = $this->generateUniqueUsername('testuser');
        $testUser = $this->createTestUser($testUsername, 'samepassword123!', ['ROLE_USER']);
        
        // Get user auth token
        $userToken = $this->getAuthToken($testUsername, 'samepassword123!');
        
        $passwordChangeData = [
            'userId' => $testUser->getId(),
            'oldPassword' => 'samepassword123!',
            'newPassword' => 'samepassword123!', // Same as old password
            'passwordConfirmation' => 'samepassword123!'
        ];

        $this->client->request(Request::METHOD_POST, '/api/password-changes', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $userToken,
        ], json_encode($passwordChangeData));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    private function getAuthToken(string $username, string $password): string
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