<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\Command\User\CreateUserCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Handles CreateUserCommand to create a new user in the system.
 */
final class CreateUserCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CreateUserCommand $command): mixed
    {
        $user = new User($command->username, $command->plainPassword);
        $user->setRoles($command->roles);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->plainPassword);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
}