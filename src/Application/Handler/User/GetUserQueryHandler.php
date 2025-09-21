<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\DTO\UserDTO;
use App\Application\Query\User\GetUserQuery;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetUserQuery to retrieve a single user by ID.
 */
final class GetUserQueryHandler
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetUserQuery $query): mixed
    {
        $user = $this->userRepository->find($query->id);
        
        if (!$user || $user->isDeleted()) {
            return null; // Return null for not found users
        }
        
        return $this->mapUserToDTO($user);
    }
    
    private function mapUserToDTO(User $user): UserDTO
    {
        return new UserDTO(
            id: $user->getId(),
            username: $user->getUsername(),
            roles: $user->getRoles(),
            createdAt: \DateTimeImmutable::createFromInterface($user->getCreatedAt()),
            updatedAt: \DateTimeImmutable::createFromInterface($user->getUpdatedAt()),
            deletedAt: $user->getDeletedAt() ? \DateTimeImmutable::createFromInterface($user->getDeletedAt()) : null
        );
    }
}