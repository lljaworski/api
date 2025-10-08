<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Tests\Trait\DatabaseTestTrait;
use App\Tests\Trait\RequestTrait;

class UserCrudTest extends WebTestCase
{
    use DatabaseTestTrait;
    use RequestTrait;
    
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ?string $adminToken = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Ensure admin user exists for testing
        $this->ensureTestAdmin();
    }

    public function testGetUsersCollectionRequiresAuth(): void
    {
        $this->client->request('GET', '/api/users');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }

    public function testGetUsersCollectionWithAuth(): void
    {
        $this->requestAsAdmin('GET', '/api/users');
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $responseData);
        $this->assertIsArray($responseData['member']);
        
        // Check that admin user is in the response and not soft deleted
        $adminUser = array_filter($responseData['member'], fn($user) => $user['username'] === 'admin');
        $this->assertNotEmpty($adminUser);
    }

    public function testCreateUser(): void
    {
        $username = $this->generateUniqueUsername('newuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($username, $responseData['username']);
        $this->assertContains('ROLE_USER', $responseData['roles']);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertArrayHasKey('updatedAt', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);
        
        // Verify user was created in database
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->assertNotNull($user);
        $this->assertFalse($user->isDeleted());
        
        // Track for cleanup
        $this->createdEntities[] = $user;
    }

    public function testCreateUserWithInvalidData(): void
    {
        $userData = [
            'username' => 'ab', // Invalid: too short (min 3)
            'password' => '12345', // Invalid: too short (min 6)
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(422, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $responseData);
    }

    public function testGetSingleUser(): void
    {
        // First create a user
        $username = $this->generateUniqueUsername('getuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $createResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $createResponseData['id'];
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $this->createdEntities[] = $user;
        
        // Now get the user
        $this->requestAsAdmin('GET', '/api/users/' . $userId);
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($username, $responseData['username']);
        $this->assertContains('ROLE_USER', $responseData['roles']);
        $this->assertEquals($userId, $responseData['id']);
        $this->assertArrayNotHasKey('password', $responseData);
    }

    public function testUpdateUser(): void
    {
        // First create a user
        $username = $this->generateUniqueUsername('updateuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $createResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $createResponseData['id'];
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $this->createdEntities[] = $user;
        
        // Now update the user
        $newUsername = $this->generateUniqueUsername('updateduser');
        $updateData = [
            'username' => $newUsername,
            'password' => 'newpassword123',
            'roles' => ['ROLE_ADMIN']
        ];
        
        $this->requestAsAdmin('PUT', '/api/users/' . $userId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($updateData));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($newUsername, $responseData['username']);
        $this->assertContains('ROLE_ADMIN', $responseData['roles']);
        $this->assertArrayNotHasKey('password', $responseData);
        
        // Verify password was updated by trying to authenticate
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $newUsername,
            'password' => 'newpassword123'
        ]));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPartialUpdateUser(): void
    {
        // First create a user
        $username = $this->generateUniqueUsername('patchuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $createResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $createResponseData['id'];
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $this->createdEntities[] = $user;
        
        // Now partially update the user (only roles)
        $patchData = [
            'roles' => ['ROLE_ADMIN']
        ];
        
        $this->requestAsAdmin('PATCH', '/api/users/' . $userId, [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode($patchData));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($username, $responseData['username']); // Should remain unchanged
        $this->assertContains('ROLE_ADMIN', $responseData['roles']); // Should be updated
        
        // Verify password remains unchanged by trying to authenticate with original password
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => 'password123'
        ]));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testSoftDeleteUser(): void
    {
        // First create a user
        $username = $this->generateUniqueUsername('deleteuser');
        $userData = [
            'username' => $username,
            'password' => 'password123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $createResponseData = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $createResponseData['id'];
        
        // Track for cleanup
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $this->createdEntities[] = $user;
        
        // Now soft delete the user
        $this->requestAsAdmin('DELETE', '/api/users/' . $userId);
        
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());
        
        // Verify user is soft deleted in database
        $this->entityManager->clear();
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNotNull($deletedUser); // User still exists in database
        $this->assertTrue($deletedUser->isDeleted()); // But is marked as deleted
        $this->assertNotNull($deletedUser->getDeletedAt());
        
        // Verify user is not in the collection anymore
        $this->requestAsAdmin('GET', '/api/users');
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $deletedUserInCollection = array_filter($responseData['member'] ?? [], fn($user) => $user['id'] === $userId);
        $this->assertEmpty($deletedUserInCollection); // Soft deleted user should not appear in collection
        
        // Verify individual user endpoint returns 404 for soft deleted user
        $this->requestAsAdmin('GET', '/api/users/' . $userId);
        
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteNonExistentUser(): void
    {
        $this->requestAsAdmin('DELETE', '/api/users/99999');
        
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testGetNonExistentUser(): void
    {
        $this->requestAsAdmin('GET', '/api/users/99999');
        
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUserOperationsRequireAdminRole(): void
    {
        // Create a regular user with unique username
        $username = $this->generateUniqueUsername('regularuser');
        
        // Check if regular user already exists and remove it
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }
        
        // Create a regular user
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $regularUser = new User($username, 'password123');
        $regularUser->setRoles(['ROLE_USER']);
        $hashedPassword = $passwordHasher->hashPassword($regularUser, 'password123');
        $regularUser->setPassword($hashedPassword);
        
        $this->entityManager->persist($regularUser);
        $this->entityManager->flush();
        
        // Track for cleanup
        $this->createdEntities[] = $regularUser;
        
        // Get token for regular user
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => 'password123'
        ]));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $regularUserToken = $responseData['token'];
        
        // Test that regular user cannot access user operations
        $this->client->request('GET', '/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
        ]);
        
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        
        $this->client->request('POST', '/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $regularUserToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'test', 'password' => 'test123']));
        
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testPasswordIsHashedOnCreate(): void
    {
        $username = $this->generateUniqueUsername('hashtest');
        $userData = [
            'username' => $username,
            'password' => 'plainpassword123',
            'roles' => ['ROLE_USER']
        ];
        
        $this->requestAsAdmin('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));
        
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        
        // Verify password was hashed
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->assertNotNull($user);
        $this->assertNotEquals('plainpassword123', $user->getPassword());
        
        // Track for cleanup
        $this->createdEntities[] = $user;
        
        // Verify can authenticate with original password
        $this->client->request('POST', '/api/login_check', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => $username,
            'password' => 'plainpassword123'
        ]));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
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