<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UserStatsDTO
{
    public function __construct(
        public readonly int $total,
        public readonly int $active,
        public readonly int $inactive,
        public readonly int $adminCount,
        public readonly int $regularUserCount,
        public readonly string $timestamp,
    ) {}
}