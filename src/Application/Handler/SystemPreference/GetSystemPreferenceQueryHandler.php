<?php

declare(strict_types=1);

namespace App\Application\Handler\SystemPreference;

use App\Application\DTO\SystemPreferenceDTO;
use App\Application\Query\SystemPreference\GetSystemPreferenceQuery;
use App\Entity\SystemPreference;
use App\Repository\SystemPreferenceRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetSystemPreferenceQuery to retrieve a single system preference by ID.
 */
final class GetSystemPreferenceQueryHandler
{
    public function __construct(
        private readonly SystemPreferenceRepository $preferenceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetSystemPreferenceQuery $query): mixed
    {
        $preference = $this->preferenceRepository->find($query->id);
        
        if (!$preference) {
            return null; // Return null for not found preferences
        }
        
        return $this->mapPreferenceToDTO($preference);
    }
    
    private function mapPreferenceToDTO(SystemPreference $preference): SystemPreferenceDTO
    {
        return new SystemPreferenceDTO(
            id: $preference->getId(),
            preferenceKey: $preference->getPreferenceKey(),
            value: $preference->getValue(),
            createdAt: \DateTimeImmutable::createFromInterface($preference->getCreatedAt()),
            updatedAt: \DateTimeImmutable::createFromInterface($preference->getUpdatedAt())
        );
    }
}
