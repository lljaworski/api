<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for User data.
 */
final class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly array $roles,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $deletedAt = null
    ) {
    }
    
    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }
}