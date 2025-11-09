<?php

declare(strict_types=1);

namespace App\Application\Handler\SystemPreference;

use App\Application\Command\SystemPreference\DeleteSystemPreferenceCommand;
use App\Repository\SystemPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles DeleteSystemPreferenceCommand to delete a system preference.
 */
final class DeleteSystemPreferenceCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SystemPreferenceRepository $preferenceRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(DeleteSystemPreferenceCommand $command): mixed
    {
        $preference = $this->preferenceRepository->find($command->id);
        
        if (!$preference) {
            throw new NotFoundHttpException('System preference not found');
        }
        
        $this->entityManager->remove($preference);
        $this->entityManager->flush();
        
        return null;
    }
}
