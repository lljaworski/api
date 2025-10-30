<?php

declare(strict_types=1);

namespace App\State;

use App\Application\DTO\InvoiceCollectionDTO;
use App\Application\DTO\InvoiceDTO;
use App\Application\Query\Invoice\GetInvoiceQuery;
use App\Application\Query\Invoice\GetInvoicesQuery;
use App\Entity\Invoice;

/**
 * Invoice state provider that extends the abstract CQRS provider.
 * Uses the EntityFromDtoFactory to eliminate code duplication.
 */
final class InvoiceProvider extends AbstractCqrsProvider
{
    protected function createCollectionQuery(int $page, int $limit, ?string $search): object
    {
        return new GetInvoicesQuery($page, $limit, $search);
    }

    protected function createSingleQuery(int $id): object
    {
        return new GetInvoiceQuery($id);
    }

    protected function createEntityFromDTO(object $dto): object
    {
        /** @var InvoiceDTO $dto */
        return EntityFromDtoFactory::createInvoiceFromDTO($dto);
    }

    protected function getItemsFromCollection(object $collectionDTO): array
    {
        /** @var InvoiceCollectionDTO $collectionDTO */
        return $collectionDTO->invoices;
    }

    protected function getTotalFromCollection(object $collectionDTO): int
    {
        /** @var InvoiceCollectionDTO $collectionDTO */
        return $collectionDTO->total;
    }
}