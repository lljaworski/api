<?php

declare(strict_types=1);

namespace App\Dictionary;

use App\Application\DTO\DictionaryItem;

/**
 * Interface for dictionary providers.
 * Implementations provide dictionary data from various sources (enums, repositories, etc.).
 */
interface DictionaryProviderInterface
{
    /**
     * Get the dictionary type this provider handles.
     */
    public function getType(): string;

    /**
     * Get all dictionary items for this type.
     * 
     * @return DictionaryItem[]
     */
    public function getItems(): array;

    /**
     * Check if this provider supports the given dictionary type.
     */
    public function supports(string $type): bool;
}