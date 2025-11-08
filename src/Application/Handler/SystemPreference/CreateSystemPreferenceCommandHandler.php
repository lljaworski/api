<?php

declare(strict_types=1);

namespace App\Application\Handler\SystemPreference;

use App\Application\Command\SystemPreference\CreateSystemPreferenceCommand;
use App\Entity\SystemPreference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles CreateSystemPreferenceCommand to create a new system preference.
 */
final class CreateSystemPreferenceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CreateSystemPreferenceCommand $command): mixed
    {
        $preference = new SystemPreference(
            $command->preferenceKey,
            $command->value
        );
        
        $this->entityManager->persist($preference);
        $this->entityManager->flush();
        
        return $preference;
    }
}
