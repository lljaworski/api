<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for paginated collection of users.
 */
final class UserCollectionDTO
{
    /**
     * @param UserDTO[] $users
     */
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $page = 1,
        public readonly int $itemsPerPage = 30
    ) {
    }
    
    public function hasNextPage(): bool
    {
        return ($this->page * $this->itemsPerPage) < $this->total;
    }
    
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}