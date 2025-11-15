<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\Command\User\UpdateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Handles UpdateUserCommand to update an existing user in the system.
 */
final class UpdateUserCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(UpdateUserCommand $command): mixed
    {
        $user = $this->userRepository->find($command->id);
        
        if (!$user || $user->isDeleted()) {
            throw new NotFoundHttpException('User not found');
        }
        
        // Update username if provided
        if ($command->username !== null) {
            $user->setUsername($command->username);
        }
        
        // Update roles if provided
        if ($command->roles !== null) {
            $user->setRoles($command->roles);
        }
        
        $this->entityManager->flush();
        
        return $user;
    }
}