<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\CompanyCollectionDTO;
use App\Application\DTO\CompanyDTO;
use App\Application\Query\Company\GetCompaniesQuery;
use App\Application\Query\Company\GetCompanyQuery;
use App\Entity\Company;

/**
 * Company state provider that extends the abstract CQRS provider.
 * Uses the EntityFromDtoFactory to eliminate code duplication.
 */
final class CompanyProvider extends AbstractCqrsProvider
{
    protected function createCollectionQuery(int $page, int $limit, ?string $search): object
    {
        return new GetCompaniesQuery($page, $limit, $search);
    }

    protected function createSingleQuery(int $id): object
    {
        return new GetCompanyQuery($id);
    }

    protected function createEntityFromDTO(object $dto): object
    {
        /** @var CompanyDTO $dto */
        return EntityFromDtoFactory::createCompanyFromDTO($dto);
    }

    protected function getItemsFromCollection(object $collectionDTO): array
    {
        /** @var CompanyCollectionDTO $collectionDTO */
        return $collectionDTO->companies;
    }

    protected function getTotalFromCollection(object $collectionDTO): int
    {
        /** @var CompanyCollectionDTO $collectionDTO */
        return $collectionDTO->total;
    }
}