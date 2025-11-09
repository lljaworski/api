<?php

declare(strict_types=1);

namespace App\Application\Handler\SystemPreference;

use App\Application\Command\SystemPreference\UpdateSystemPreferenceCommand;
use App\Repository\SystemPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles UpdateSystemPreferenceCommand to update an existing system preference.
 */
final class UpdateSystemPreferenceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SystemPreferenceRepository $preferenceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(UpdateSystemPreferenceCommand $command): mixed
    {
        $preference = $this->preferenceRepository->find($command->id);
        
        if (!$preference) {
            throw new NotFoundHttpException('System preference not found');
        }
        
        // Update key if provided
        if ($command->preferenceKey !== null) {
            $preference->setPreferenceKey($command->preferenceKey);
        }
        
        // Update value if provided
        if ($command->value !== null) {
            $preference->setValue($command->value);
        }
        
        $this->entityManager->flush();
        
        return $preference;
    }
}
