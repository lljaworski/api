<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class JsonCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (!$object instanceof PaginatorInterface) {
            return $this->normalizer->normalize($object, $format, $context);
        }

        // For JSON format, include pagination metadata
        if ($format === 'json') {
            $data = [];
            foreach ($object as $item) {
                $data[] = $this->normalizer->normalize($item, $format, $context);
            }

            return [
                'data' => $data,
                'pagination' => [
                    'total' => $object->getTotalItems(),
                    'count' => count($data),
                    'currentPage' => $object->getCurrentPage(),
                    'itemsPerPage' => $object->getItemsPerPage(),
                    'totalPages' => (int) ceil($object->getTotalItems() / $object->getItemsPerPage())
                ]
            ];
        }

        // For other formats, use default normalization
        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PaginatorInterface && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaginatorInterface::class => true,
        ];
    }
}