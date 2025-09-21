<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Abstract base class for commands with common functionality.
 */
abstract class AbstractCommand implements CommandInterface
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