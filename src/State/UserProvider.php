<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\DTO\UserCollectionDTO;
use App\Application\DTO\UserDTO;
use App\Application\Query\User\GetUserQuery;
use App\Application\Query\User\GetUsersQuery;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @implements ProviderInterface<User>
 */
final class UserProvider implements ProviderInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Handle single user retrieval
        if (isset($uriVariables['id'])) {
            $query = new GetUserQuery((int) $uriVariables['id']);
            
            $envelope = $this->queryBus->dispatch($query);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            /** @var UserDTO|null $userDTO */
            $userDTO = $handledStamp->getResult();
            
            if ($userDTO === null) {
                return null; // This will result in a 404 from API Platform
            }
            
            // Convert DTO back to entity for API Platform compatibility
            $user = $this->createUserEntityFromDTO($userDTO);
            return $user;
        }

        // Handle user collection
        $page = (int) ($context['filters']['page'] ?? 1);
        $itemsPerPage = (int) ($context['filters']['itemsPerPage'] ?? 30);
        $search = $context['filters']['search'] ?? null;
        
        $query = new GetUsersQuery($page, $itemsPerPage, $search);
        $envelope = $this->queryBus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);
        
        /** @var UserCollectionDTO $collectionDTO */
        $collectionDTO = $handledStamp->getResult();
        
        // Convert DTOs back to entities for API Platform compatibility
        $users = array_map([$this, 'createUserEntityFromDTO'], $collectionDTO->users);
        
        return $users;
    }
    
    private function createUserEntityFromDTO(UserDTO $dto): User
    {
        $user = new User($dto->username, ''); // Password not needed for read operations
        
        // Use reflection to set private properties since this is for read-only display
        $reflection = new \ReflectionClass($user);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $dto->id);
        
        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($user, $dto->createdAt);
        
        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $updatedAtProperty->setAccessible(true);
        $updatedAtProperty->setValue($user, $dto->updatedAt);
        
        if ($dto->deletedAt) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($user, $dto->deletedAt);
        }
        
        $user->setRoles($dto->roles);
        
        return $user;
    }
}