<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Enum\PreferenceKey;

/**
 * Data Transfer Object for SystemPreference data.
 */
final class SystemPreferenceDTO
{
    public function __construct(
        public readonly int $id,
        public readonly PreferenceKey $preferenceKey,
        public readonly mixed $value,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt
    ) {
    }
}
