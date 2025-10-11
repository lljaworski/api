<?php

declare(strict_types=1);

namespace App\Dictionary\Provider;

use App\Application\DTO\DictionaryItem;
use App\Dictionary\DictionaryProviderInterface;

/**
 * Dictionary provider for PHP enums.
 * Converts enum cases to dictionary items where enum name = id and enum value = name.
 */
final class EnumDictionaryProvider implements DictionaryProviderInterface
{
    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    public function __construct(
        private readonly string $type,
        private readonly string $enumClass
    ) {
        if (!enum_exists($this->enumClass)) {
            throw new \InvalidArgumentException("Class {$this->enumClass} is not an enum");
        }

        if (!is_subclass_of($this->enumClass, \BackedEnum::class)) {
            throw new \InvalidArgumentException("Enum {$this->enumClass} must be a backed enum");
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function supports(string $type): bool
    {
        return $this->type === $type;
    }

    public function getItems(): array
    {
        /** @var \BackedEnum[] $cases */
        $cases = $this->enumClass::cases();

        return array_map(
            fn(\BackedEnum $case) => new DictionaryItem(
                id: $case->name,
                name: (string) $case->value
            ),
            $cases
        );
    }
}