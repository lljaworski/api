<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\SystemPreferenceCollectionDTO;
use App\Application\DTO\SystemPreferenceDTO;
use App\Application\Query\SystemPreference\GetSystemPreferenceQuery;
use App\Application\Query\SystemPreference\GetSystemPreferencesQuery;

/**
 * SystemPreference state provider that extends the abstract CQRS provider.
 * Uses the EntityFromDtoFactory to eliminate code duplication.
 */
final class SystemPreferenceProvider extends AbstractCqrsProvider
{
    protected function createCollectionQuery(int $page, int $limit, ?string $search): object
    {
        return new GetSystemPreferencesQuery($page, $limit);
    }

    protected function createSingleQuery(int $id): object
    {
        return new GetSystemPreferenceQuery($id);
    }

    protected function createEntityFromDTO(object $dto): object
    {
        /** @var SystemPreferenceDTO $dto */
        return EntityFromDtoFactory::createSystemPreferenceFromDTO($dto);
    }

    protected function getItemsFromCollection(object $collectionDTO): array
    {
        /** @var SystemPreferenceCollectionDTO $collectionDTO */
        return $collectionDTO->preferences;
    }

    protected function getTotalFromCollection(object $collectionDTO): int
    {
        /** @var SystemPreferenceCollectionDTO $collectionDTO */
        return $collectionDTO->total;
    }
}
