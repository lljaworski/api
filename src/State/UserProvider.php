<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\UserCollectionDTO;
use App\Application\DTO\UserDTO;
use App\Application\Query\User\GetUserQuery;
use App\Application\Query\User\GetUsersQuery;
use App\Entity\User;

/**
 * User state provider that extends the abstract CQRS provider.
 * Uses the EntityFromDtoFactory to eliminate code duplication.
 */
final class UserProvider extends AbstractCqrsProvider
{
    protected function createCollectionQuery(int $page, int $limit, ?string $search): object
    {
        return new GetUsersQuery($page, $limit, $search);
    }

    protected function createSingleQuery(int $id): object
    {
        return new GetUserQuery($id);
    }

    protected function createEntityFromDTO(object $dto): object
    {
        /** @var UserDTO $dto */
        return EntityFromDtoFactory::createUserFromDTO($dto);
    }

    protected function getItemsFromCollection(object $collectionDTO): array
    {
        /** @var UserCollectionDTO $collectionDTO */
        return $collectionDTO->users;
    }

    protected function getTotalFromCollection(object $collectionDTO): int
    {
        /** @var UserCollectionDTO $collectionDTO */
        return $collectionDTO->total;
    }
}