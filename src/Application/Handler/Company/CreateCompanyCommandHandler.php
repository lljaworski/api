<?php

declare(strict_types=1);

namespace App\Application\Handler\Company;

use App\Application\Command\Company\CreateCompanyCommand;
use App\Entity\Company;
use App\Service\CompanyHydrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles CreateCompanyCommand to create a new company in the system.
 */
final class CreateCompanyCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyHydrator $companyHydrator
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(CreateCompanyCommand $command): mixed
    {
        $company = new Company($command->name);
        
        $this->companyHydrator->hydrateFromCreateCommand($company, $command);
        
        $this->entityManager->persist($company);
        $this->entityManager->flush();
        
        return $company;
    }
}