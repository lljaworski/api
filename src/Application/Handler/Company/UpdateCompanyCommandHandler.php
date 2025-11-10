<?php

declare(strict_types=1);

namespace App\Application\Handler\Company;

use App\Application\Command\Company\UpdateCompanyCommand;
use App\Repository\CompanyRepository;
use App\Service\CompanyHydrator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles UpdateCompanyCommand to update an existing company.
 */
final class UpdateCompanyCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanyHydrator $companyHydrator
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(UpdateCompanyCommand $command): mixed
    {
        $company = $this->companyRepository->find($command->id);
        
        if (!$company || $company->isDeleted()) {
            throw new NotFoundHttpException('Company not found');
        }
        
        $this->companyHydrator->hydrateFromUpdateCommand($company, $command);
        
        $this->entityManager->flush();
        
        return $company;
    }
}