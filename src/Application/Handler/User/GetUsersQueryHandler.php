<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\DTO\UserCollectionDTO;
use App\Application\DTO\UserDTO;
use App\Application\Query\User\GetUsersQuery;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetUsersQuery to retrieve a paginated collection of users.
 */
final class GetUsersQueryHandler
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetUsersQuery $query): mixed
    {
        $offset = ($query->page - 1) * $query->itemsPerPage;
        
        // Get users with optional search
        $users = $this->userRepository->findActiveUsers(
            limit: $query->itemsPerPage,
            offset: $offset,
            search: $query->search
        );
        
        // Get total count for pagination
        $total = $this->userRepository->countActiveUsers($query->search);
        
        $userDTOs = array_map([$this, 'mapUserToDTO'], $users);
        
        return new UserCollectionDTO(
            users: $userDTOs,
            total: $total,
            page: $query->page,
            itemsPerPage: $query->itemsPerPage
        );
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