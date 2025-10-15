<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for paginated Company collection data.
 */
final class CompanyCollectionDTO
{
    /**
     * @param CompanyDTO[] $companies
     */
    public function __construct(
        public readonly array $companies,
        public readonly int $total,
        public readonly int $page,
        public readonly int $itemsPerPage
    ) {
    }
    
    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->itemsPerPage);
    }
    
    public function hasNext(): bool
    {
        return $this->page < $this->getTotalPages();
    }
    
    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }
}