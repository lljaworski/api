<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\Command\User\DeleteUserCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles DeleteUserCommand to soft delete a user from the system.
 */
final class DeleteUserCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(DeleteUserCommand $command): mixed
    {
        $user = $this->userRepository->find($command->id);
        
        if (!$user || $user->isDeleted()) {
            throw new NotFoundHttpException('User not found');
        }
        
        $user->softDelete();
        $this->entityManager->flush();
        
        return null;
    }
}