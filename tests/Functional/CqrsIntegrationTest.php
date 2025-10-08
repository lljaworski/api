<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\Command\User\CreateUserCommand;
use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Application\Query\User\GetUserQuery;
use App\Application\Query\User\GetUsersQuery;
use App\Tests\Trait\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CqrsIntegrationTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private MessageBusInterface $commandBus;
    private MessageBusInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTestAdmin();
        
        self::bootKernel();
        
        $container = static::getContainer();
        $this->commandBus = $container->get('command.bus');
        $this->queryBus = $container->get('query.bus');
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testCqrsWorkflowCreateReadUpdateDelete(): void
    {
        // Test Create Command
        $username = $this->generateUniqueUsername();
        $createCommand = new CreateUserCommand(
            username: $username,
            plainPassword: 'password123',
            roles: ['ROLE_USER']
        );

        $envelope = $this->commandBus->dispatch($createCommand);
        $createdUser = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($createdUser);
        $this->assertEquals($username, $createdUser->getUsername());
        $this->assertEquals(['ROLE_USER'], $createdUser->getRoles());
        $this->assertTrue($createdUser->isActive());

        $userId = $createdUser->getId();

        // Test Get User Query
        $getUserQuery = new GetUserQuery($userId);
        $envelope = $this->queryBus->dispatch($getUserQuery);
        $userDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($userDto);
        $this->assertEquals($userId, $userDto->id);
        $this->assertEquals($username, $userDto->username);
        $this->assertEquals(['ROLE_USER'], $userDto->roles);
        $this->assertTrue($userDto->isActive());

        // Test Get Users Query
        $getUsersQuery = new GetUsersQuery(page: 1, itemsPerPage: 10);
        $envelope = $this->queryBus->dispatch($getUsersQuery);
        $usersCollection = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($usersCollection);
        $this->assertGreaterThanOrEqual(1, $usersCollection->total);
        $this->assertNotEmpty($usersCollection->users);

        // Test Update Command
        $updateCommand = new UpdateUserCommand(
            id: $userId,
            username: $username, // Keep same username
            plainPassword: 'newpassword456',
            roles: ['ROLE_USER', 'ROLE_ADMIN']
        );

        $envelope = $this->commandBus->dispatch($updateCommand);
        $updatedUser = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($updatedUser);
        $this->assertEquals($userId, $updatedUser->getId());
        $this->assertEquals($username, $updatedUser->getUsername());
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $updatedUser->getRoles());

        // Verify update with query
        $getUserQuery = new GetUserQuery($userId);
        $envelope = $this->queryBus->dispatch($getUserQuery);
        $updatedUserDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $updatedUserDto->roles);

        // Test Delete Command (soft delete)
        $deleteCommand = new DeleteUserCommand($userId);
        $this->commandBus->dispatch($deleteCommand);

        // Verify user is soft deleted
        $getUserQuery = new GetUserQuery($userId);
        $envelope = $this->queryBus->dispatch($getUserQuery);
        $deletedUserDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNull($deletedUserDto); // Should return null for soft-deleted users
    }

    public function testGetUsersQueryWithSearch(): void
    {
        // Create test users
        $username1 = $this->generateUniqueUsername();
        $username2 = $this->generateUniqueUsername();

        $createCommand1 = new CreateUserCommand(
            username: $username1,
            plainPassword: 'password123',
            roles: ['ROLE_USER']
        );

        $createCommand2 = new CreateUserCommand(
            username: $username2,
            plainPassword: 'password123',
            roles: ['ROLE_USER']
        );

        $this->commandBus->dispatch($createCommand1);
        $this->commandBus->dispatch($createCommand2);

        // Test search functionality
        $searchQuery = new GetUsersQuery(page: 1, itemsPerPage: 10, search: substr($username1, 0, 10));
        $envelope = $this->queryBus->dispatch($searchQuery);
        $searchResults = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNotNull($searchResults);
        $this->assertGreaterThanOrEqual(1, $searchResults->total);
        
        // Should find at least our test user
        $foundTestUser = false;
        foreach ($searchResults->users as $userDto) {
            if ($userDto->username === $username1) {
                $foundTestUser = true;
                break;
            }
        }
        $this->assertTrue($foundTestUser, 'Search should find the created test user');
    }

    public function testQueryNonExistentUser(): void
    {
        $getUserQuery = new GetUserQuery(99999); // Non-existent ID
        $envelope = $this->queryBus->dispatch($getUserQuery);
        $userDto = $envelope->last(HandledStamp::class)->getResult();

        $this->assertNull($userDto);
    }
}