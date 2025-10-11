<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\DictionaryProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/dictionaries/{type}',
            requirements: ['type' => '[a-zA-Z_][a-zA-Z0-9_]*'],
            security: "is_granted('ROLE_USER')",
            provider: DictionaryProvider::class,
            normalizationContext: ['groups' => ['dictionary:read']],
            name: 'get_dictionary'
        )
    ],
    shortName: 'Dictionary',
    description: 'Dictionary resource for frontend select inputs and other UI components'
)]
class Dictionary
{
    #[Groups(['dictionary:read'])]
    public string $type;

    /** @var array<int, array{id: string, name: string}> */
    #[Groups(['dictionary:read'])]
    public array $items;

    /**
     * @param array<int, array{id: string, name: string}> $items
     */
    public function __construct(string $type, array $items)
    {
        $this->type = $type;
        $this->items = $items;
    }
}