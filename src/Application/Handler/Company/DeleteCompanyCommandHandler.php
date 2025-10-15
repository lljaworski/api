<?php

declare(strict_types=1);

namespace App\Application\Handler\Company;

use App\Application\Command\Company\DeleteCompanyCommand;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles DeleteCompanyCommand to soft delete a company from the system.
 */
final class DeleteCompanyCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepository $companyRepository
    ) {
    }

    #[AsMessageHandler]
    public function __invoke(DeleteCompanyCommand $command): mixed
    {
        $company = $this->companyRepository->find($command->id);
        
        if (!$company || $company->isDeleted()) {
            throw new NotFoundHttpException('Company not found');
        }
        
        $company->softDelete();
        $this->entityManager->flush();
        
        return null;
    }
}