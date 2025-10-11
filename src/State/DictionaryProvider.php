<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dictionary;
use App\Dictionary\DictionaryRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Dictionary>
 */
final class DictionaryProvider implements ProviderInterface
{
    public function __construct(
        private readonly DictionaryRegistry $dictionaryRegistry
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Dictionary
    {
        $type = $uriVariables['type'] ?? null;

        if (!$type) {
            throw new NotFoundHttpException('Dictionary type is required');
        }

        if (!$this->dictionaryRegistry->hasDictionary($type)) {
            throw new NotFoundHttpException("Dictionary type '{$type}' not found");
        }

        try {
            $items = $this->dictionaryRegistry->getDictionary($type);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        // Convert DictionaryItem DTOs to simple arrays for API response
        $itemsArray = array_map(
            fn($item) => [
                'id' => $item->id,
                'name' => $item->name
            ],
            $items
        );

        return new Dictionary($type, $itemsArray);
    }
}