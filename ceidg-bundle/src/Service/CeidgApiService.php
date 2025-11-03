<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Service;

use LukaszJaworski\CeidgBundle\Exception\CeidgApiException;
use LukaszJaworski\CeidgBundle\Model\FirmaCeidgDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service for interacting with CEIDG (Centralna Ewidencja i Informacja o Działalności Gospodarczej) API.
 * 
 * Provides methods to fetch Polish business registry data with caching support.
 */
final readonly class CeidgApiService
{
    private const REQUEST_TIMEOUT = 30;
    private const ACCEPT_HEADER = 'application/json';
    private const CACHE_TTL = 3600; // 1 hour cache
    private const CACHE_KEY_PREFIX = 'ceidg_company_';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiUrl,
        private string $apiKey,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    /**
     * Find company by NIP (Polish Tax Identification Number).
     * 
     * Results are cached for 1 hour to reduce external API calls.
     * 
     * @param string $nip The NIP number to search for
     * @return FirmaCeidgDTO|null Returns company DTO if found, null otherwise
     * @throws CeidgApiException When API returns error or transport fails
     */
    public function findByNip(string $nip): ?FirmaCeidgDTO
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $nip;
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($nip): ?FirmaCeidgDTO {
                $item->expiresAfter(self::CACHE_TTL);
                
                $this->logger->info('Fetching company data from CEIDG API', [
                    'nip' => $nip,
                ]);
                
                return $this->fetchCompanyFromApi($nip);
            });
        } catch (CeidgApiException $e) {
            // Re-throw CEIDG-specific exceptions
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch company data from CEIDG', [
                'nip' => $nip,
                'error' => $e->getMessage(),
            ]);
            
            throw CeidgApiException::fromTransportError('Failed to fetch company data');
        }
    }

    /**
     * Fetch company data directly from CEIDG API without caching.
     * 
     * @param string $nip The NIP number to search for
     * @return FirmaCeidgDTO|null Returns company DTO if found, null otherwise
     * @throws CeidgApiException When API returns error or transport fails
     */
    private function fetchCompanyFromApi(string $nip): ?FirmaCeidgDTO
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, [
                'headers' => [
                    'Accept' => self::ACCEPT_HEADER,
                    'Authorization' => sprintf('Bearer %s', $this->apiKey),
                ],
                'query' => [
                    'nip' => $nip,
                ],
                'timeout' => self::REQUEST_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();

            // Handle successful responses without content
            if ($this->isEmptyResponse($statusCode)) {
                return null;
            }

            // Handle 400 Bad Request (e.g., invalid NIP format) as not found
            if ($statusCode === Response::HTTP_BAD_REQUEST) {
                $content = $response->getContent(false);
                // If it's an invalid NIP error, return null instead of throwing
                if (str_contains($content, 'NIEPOPRAWNY_NUMER_NIP') || str_contains($content, 'Niepoprawny identyfikator')) {
                    $this->logger->info('Invalid NIP format from CEIDG API', [
                        'nip' => $nip,
                        'response' => $content,
                    ]);
                    return null;
                }
                // For other 400 errors, log and throw exception
                $this->logger->warning('Bad request to CEIDG API', [
                    'nip' => $nip,
                    'status_code' => $statusCode,
                    'response' => $content,
                ]);
                throw CeidgApiException::fromApiError($statusCode, $content);
            }

            // Handle other error responses
            if ($this->isErrorResponse($statusCode)) {
                $errorMessage = $this->getErrorMessage($response);
                $this->logger->error('CEIDG API error response', [
                    'nip' => $nip,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                ]);
                throw CeidgApiException::fromApiError(
                    $statusCode,
                    $errorMessage
                );
            }

            return $this->parseCompanyData($response->toArray());

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('CEIDG API transport error', [
                'nip' => $nip,
                'error' => $e->getMessage(),
            ]);
            throw CeidgApiException::fromTransportError($e->getMessage());
        }
    }

    private function isEmptyResponse(int $statusCode): bool
    {
        return $statusCode === Response::HTTP_NOT_FOUND 
            || $statusCode === Response::HTTP_NO_CONTENT;
    }

    private function isErrorResponse(int $statusCode): bool
    {
        return $statusCode >= Response::HTTP_BAD_REQUEST;
    }

    private function getErrorMessage(ResponseInterface $response): string
    {
        try {
            return $response->getContent(false);
        } catch (\Throwable) {
            return 'Unknown error';
        }
    }

    private function parseCompanyData(array $data): ?FirmaCeidgDTO
    {
        // Handle empty response or no data
        // API returns 'firmy' (plural) key for collection endpoint
        if (empty($data) || !isset($data['firmy']) || empty($data['firmy'])) {
            return null;
        }

        // Get first company from array
        $companyData = $data['firmy'][0];

        return FirmaCeidgDTO::fromApiResponse($companyData);
    }
}
