<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Data Transfer Object for dictionary items.
 * Represents a key-value pair with id and name.
 */
final class DictionaryItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {
    }
}