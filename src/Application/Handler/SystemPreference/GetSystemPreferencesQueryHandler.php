<?php

declare(strict_types=1);

namespace App\Application\Handler\SystemPreference;

use App\Application\DTO\SystemPreferenceCollectionDTO;
use App\Application\DTO\SystemPreferenceDTO;
use App\Application\Query\SystemPreference\GetSystemPreferencesQuery;
use App\Entity\SystemPreference;
use App\Repository\SystemPreferenceRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles GetSystemPreferencesQuery to retrieve a paginated collection of system preferences.
 */
final class GetSystemPreferencesQueryHandler
{
    public function __construct(
        private readonly SystemPreferenceRepository $preferenceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(GetSystemPreferencesQuery $query): mixed
    {
        $offset = ($query->page - 1) * $query->itemsPerPage;
        
        // Get preferences with pagination
        $preferences = $this->preferenceRepository->findPreferences(
            limit: $query->itemsPerPage,
            offset: $offset
        );
        
        // Get total count for pagination
        $total = $this->preferenceRepository->countPreferences();
        
        $preferenceDTOs = array_map([$this, 'mapPreferenceToDTO'], $preferences);
        
        return new SystemPreferenceCollectionDTO(
            preferences: $preferenceDTOs,
            total: $total,
            page: $query->page,
            itemsPerPage: $query->itemsPerPage
        );
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
