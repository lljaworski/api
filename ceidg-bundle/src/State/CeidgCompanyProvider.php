<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use LukaszJaworski\CeidgBundle\ApiResource\CeidgCompany;
use LukaszJaworski\CeidgBundle\Exception\CeidgApiException;
use LukaszJaworski\CeidgBundle\Service\CeidgApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * State Provider for CeidgCompany API Resource.
 * 
 * Fetches company data from CEIDG API and handles errors appropriately.
 */
final readonly class CeidgCompanyProvider implements ProviderInterface
{
    public function __construct(
        private CeidgApiService $ceidgApiService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Provide company data from CEIDG API.
     * 
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     * @return CeidgCompany|null
     * @throws NotFoundHttpException When company is not found
     * @throws ServiceUnavailableHttpException When CEIDG API is unavailable
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?CeidgCompany
    {
        $nip = $uriVariables['nip'] ?? null;
        
        if (!$nip) {
            throw new NotFoundHttpException('NIP parameter is required');
        }

        // Validate NIP format (10 digits)
        if (!preg_match('/^\d{10}$/', $nip)) {
            throw new NotFoundHttpException(sprintf('Invalid NIP format: %s. NIP must be exactly 10 digits.', $nip));
        }

        try {
            $companyDto = $this->ceidgApiService->findByNip($nip);
            
            if ($companyDto === null) {
                throw new NotFoundHttpException(sprintf('Company with NIP %s not found in CEIDG registry', $nip));
            }

            return new CeidgCompany(
                nip: $companyDto->nip,
                nazwa: $companyDto->nazwa,
                dataRozpoczeciaDzialalnosci: $companyDto->dataRozpoczeciaDzialalnosci->format('Y-m-d'),
                dataPowstania: $companyDto->dataPowstania->format('Y-m-d'),
                status: $companyDto->status,
                dataZawieszeniaDzialalnosci: $companyDto->dataZawieszeniaDzialalnosci?->format('Y-m-d'),
                dataWznowieniaDzialalnosci: $companyDto->dataWznowieniaDzialalnosci?->format('Y-m-d'),
                dataZakonczeniaDzialalnosci: $companyDto->dataZakonczeniaDzialalnosci?->format('Y-m-d'),
            );
            
        } catch (CeidgApiException $e) {
            $this->logger->error('Failed to fetch company from CEIDG', [
                'nip' => $nip,
                'error' => $e->getMessage(),
            ]);
            
            throw new ServiceUnavailableHttpException(
                60,
                'Unable to fetch company data from CEIDG. Please try again later.'
            );
        }
    }
}
