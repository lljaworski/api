<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Abstract base provider that handles common CQRS query dispatch and pagination patterns.
 * Reduces code duplication between UserProvider and CompanyProvider.
 */
abstract class AbstractCqrsProvider implements ProviderInterface
{
    public function __construct(
        protected readonly MessageBusInterface $queryBus,
        protected readonly Pagination $pagination
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($operation, $context);
        } else {
            return $this->provideSingle($uriVariables);
        }
    }
    
    /**
     * Handle collection operations with pagination.
     */
    private function provideCollection(Operation $operation, array $context): TraversablePaginator
    {
        $offset = $this->pagination->getOffset($operation, $context);
        $limit = $this->pagination->getLimit($operation, $context);
        $page = $this->pagination->getPage($context);
        
        // API Platform SearchFilter creates filters with property names
        // For now, we'll use 'name' filter as the main search parameter
        $search = $context['filters']['name'] ?? null;
        
        $query = $this->createCollectionQuery($page, $limit, $search);
        $envelope = $this->queryBus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);
        
        $collectionDTO = $handledStamp->getResult();
        
        // Convert DTOs to entities
        $entities = array_map([$this, 'createEntityFromDTO'], $this->getItemsFromCollection($collectionDTO));
        
        // Return API Platform Paginator for proper Hydra collection format
        return new TraversablePaginator(
            new \ArrayIterator($entities),
            $offset,
            $limit,
            $this->getTotalFromCollection($collectionDTO)
        );
    }
    
    /**
     * Handle single item operations.
     */
    private function provideSingle(array $uriVariables): ?object
    {
        $query = $this->createSingleQuery((int) $uriVariables['id']);
        $envelope = $this->queryBus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);
        
        $dto = $handledStamp->getResult();
        
        if ($dto === null) {
            return null; // This will result in a 404 from API Platform
        }
        
        return $this->createEntityFromDTO($dto);
    }
    
    /**
     * Create the appropriate collection query for this provider.
     */
    abstract protected function createCollectionQuery(int $page, int $limit, ?string $search): object;
    
    /**
     * Create the appropriate single item query for this provider.
     */
    abstract protected function createSingleQuery(int $id): object;
    
    /**
     * Convert a DTO to its corresponding entity.
     */
    abstract protected function createEntityFromDTO(object $dto): object;
    
    /**
     * Get the array of items from the collection DTO.
     */
    abstract protected function getItemsFromCollection(object $collectionDTO): array;
    
    /**
     * Get the total count from the collection DTO.
     */
    abstract protected function getTotalFromCollection(object $collectionDTO): int;
}