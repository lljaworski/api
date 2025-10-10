<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\Command\User\ChangePasswordCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): User
    {
        // Find the user
        $user = $this->userRepository->findActiveById($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        // Verify old password
        if (!$this->passwordHasher->isPasswordValid($user, $command->oldPassword)) {
            throw new BadRequestHttpException('Current password is incorrect.');
        }

        // Check if new password is different from old password
        if ($this->passwordHasher->isPasswordValid($user, $command->newPassword)) {
            throw new BadRequestHttpException('New password must be different from the current password.');
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->newPassword);
        $user->setPassword($hashedPassword);

        // Persist changes
        $this->entityManager->flush();

        return $user;
    }
}