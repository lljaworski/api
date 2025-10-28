<?php

declare(strict_types=1);

namespace App\Demo\Service;

use App\Application\Command\User\CreateUserCommand;
use App\Enum\RolesEnum;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Service for generating demo users with random data.
 */
class UserGenerator
{
    private Generator $faker;

    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
        $this->faker = Factory::create();
    }

    /**
     * Generate a random username using words and numbers.
     */
    public function generateUsername(): string
    {
        $adjective = $this->faker->randomElement([
            'happy', 'bright', 'clever', 'swift', 'brave', 'calm', 'wise', 'kind',
            'bold', 'quick', 'cool', 'smart', 'strong', 'gentle', 'proud', 'keen'
        ]);
        
        $noun = $this->faker->randomElement([
            'tiger', 'eagle', 'wolf', 'fox', 'bear', 'lion', 'hawk', 'deer',
            'dolphin', 'shark', 'whale', 'falcon', 'panther', 'rabbit', 'horse', 'owl'
        ]);
        
        $number = $this->faker->numberBetween(1, 999);
        
        return sprintf('%s_%s_%d', $adjective, $noun, $number);
    }

    /**
     * Generate a random role array.
     */
    public function generateRoles(): array
    {
        $availableRoles = [
            RolesEnum::ROLE_USER->value,
            RolesEnum::ROLE_ADMIN->value,
        ];

        // 80% chance of ROLE_USER only, 20% chance of ROLE_ADMIN (which includes ROLE_USER via hierarchy)
        if ($this->faker->boolean(20)) {
            return [RolesEnum::ROLE_ADMIN->value];
        }

        return [RolesEnum::ROLE_USER->value];
    }

    /**
     * Create a single demo user.
     * 
     * @throws \Exception if user creation fails
     */
    public function createUser(?string $username = null, ?array $roles = null): array
    {
        $username = $username ?? $this->generateUsername();
        $roles = $roles ?? $this->generateRoles();
        
        $command = new CreateUserCommand(
            username: $username,
            plainPassword: 'admin123!',
            roles: $roles
        );

        $envelope = $this->commandBus->dispatch($command);
        $user = $envelope->last(HandledStamp::class)->getResult();

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'created' => true
        ];
    }

    /**
     * Check if a username already exists.
     */
    public function usernameExists(string $username): bool
    {
        // We'll implement this by trying to create and catching the exception
        // This is simpler than adding a query just for this demo feature
        return false; // For now, we'll let the command handler handle duplicates
    }
}