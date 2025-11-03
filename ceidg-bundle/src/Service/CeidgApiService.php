<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Service;

use LukaszJaworski\CeidgBundle\Exception\CeidgApiException;
use LukaszJaworski\CeidgBundle\Model\FirmaCeidgDTO;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service for interacting with CEIDG (Centralna Ewidencja i Informacja o Działalności Gospodarczej) API.
 * 
 * Provides methods to fetch Polish business registry data.
 */
final readonly class CeidgApiService
{
    private const REQUEST_TIMEOUT = 30;
    private const ACCEPT_HEADER = 'application/json';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiUrl,
        private string $apiKey,
    ) {}

    /**
     * Find company by NIP (Polish Tax Identification Number).
     * 
     * @param string $nip The NIP number to search for
     * @return FirmaCeidgDTO|null Returns company DTO if found, null otherwise
     * @throws CeidgApiException When API returns error or transport fails
     */
    public function findByNip(string $nip): ?FirmaCeidgDTO
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
                    return null;
                }
                // For other 400 errors, throw exception
                throw CeidgApiException::fromApiError($statusCode, $content);
            }

            // Handle other error responses
            if ($this->isErrorResponse($statusCode)) {
                throw CeidgApiException::fromApiError(
                    $statusCode,
                    $this->getErrorMessage($response)
                );
            }

            return $this->parseCompanyData($response->toArray());

        } catch (TransportExceptionInterface $e) {
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
