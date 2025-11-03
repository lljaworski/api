<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Exception;

use RuntimeException;

/**
 * Exception thrown when CEIDG API operations fail.
 */
class CeidgApiException extends RuntimeException
{
    /**
     * Create exception from API error response.
     */
    public static function fromApiError(int $statusCode, string $message): self
    {
        return new self(
            sprintf('CEIDG API error (HTTP %d): %s', $statusCode, $message),
            $statusCode
        );
    }

    /**
     * Create exception from transport error.
     */
    public static function fromTransportError(string $message): self
    {
        return new self(
            sprintf('CEIDG API transport error: %s', $message),
            0
        );
    }
}
