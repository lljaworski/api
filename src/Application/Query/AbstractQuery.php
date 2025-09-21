<?php

declare(strict_types=1);

namespace App\Application\Query;

/**
 * Abstract base class for queries with common functionality.
 */
abstract class AbstractQuery implements QueryInterface
{
    private readonly \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}