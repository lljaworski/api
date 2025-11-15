<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InvoiceRepository;
use App\Repository\SystemPreferenceRepository;
use App\Enum\PreferenceKey;

class InvoiceNumberGenerator
{
    private const DEFAULT_FORMAT = 'FV/{year}/{month}/{number}';

    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly SystemPreferenceRepository $systemPreferenceRepository
    ) {}

    /**
     * Generate a unique invoice number based on issue date
     * Uses configurable format from SystemPreferences
     */
    public function generate(\DateTimeInterface $issueDate): string
    {
        $format = $this->getConfiguredFormat();
        $year = $issueDate->format('Y');
        $month = $issueDate->format('m');
        $sequenceNumber = $this->invoiceRepository->getNextSequenceNumber($issueDate);

        return str_replace(
            ['{year}', '{month}', '{number}'],
            [$year, $month, sprintf('%04d', $sequenceNumber)],
            $format
        );
    }

    /**
     * Generate the next available number for the current date
     */
    public function generateForToday(): string
    {
        return $this->generate(new \DateTime());
    }

    /**
     * Validate if an invoice number follows the expected format
     */
    public function isValidFormat(string $number): bool
    {
        $format = $this->getConfiguredFormat();
        
        // Convert format template to regex pattern
        // First escape special regex characters, then replace placeholders
        $escapedFormat = preg_quote($format, '/');
        $pattern = '/^' . str_replace(
            ['\{year\}', '\{month\}', '\{number\}'],
            ['\d{4}', '\d{2}', '\d{4}'],
            $escapedFormat
        ) . '$/';
        
        return preg_match($pattern, $number) === 1;
    }

    /**
     * Extract date components from an invoice number
     * Returns array with 'year', 'month', 'sequence' or null if invalid
     */
    public function parseInvoiceNumber(string $number): ?array
    {
        if (!$this->isValidFormat($number)) {
            return null;
        }

        $format = $this->getConfiguredFormat();
        
        // Create regex with capture groups for year, month, number
        $escapedFormat = preg_quote($format, '/');
        $pattern = '/^' . str_replace(
            ['\{year\}', '\{month\}', '\{number\}'],
            ['(\d{4})', '(\d{2})', '(\d{4})'],
            $escapedFormat
        ) . '$/';
        
        if (preg_match($pattern, $number, $matches)) {
            return [
                'year' => (int) $matches[1],
                'month' => (int) $matches[2], 
                'sequence' => (int) $matches[3]
            ];
        }
        
        return null;
    }

    /**
     * Check if an invoice number is already taken
     */
    public function isNumberTaken(string $number): bool
    {
        return $this->invoiceRepository->findByNumber($number) !== null;
    }

    /**
     * Generate a unique number with retry logic (fallback for concurrent requests)
     */
    public function generateWithRetry(\DateTimeInterface $issueDate, int $maxRetries = 5): string
    {
        $attempts = 0;
        
        do {
            $number = $this->generate($issueDate);
            
            if (!$this->isNumberTaken($number)) {
                return $number;
            }
            
            $attempts++;
            
            // Add small delay to handle concurrent requests
            if ($attempts < $maxRetries) {
                usleep(rand(10000, 50000)); // 10-50ms random delay
            }
            
        } while ($attempts < $maxRetries);

        // If all retries failed, add timestamp to ensure uniqueness
        $timestamp = $issueDate->format('His');
        return sprintf(
            '%s/%s/%s/%04d-%s',
            'FV',
            $issueDate->format('Y'),
            $issueDate->format('m'),
            $this->invoiceRepository->getNextSequenceNumber($issueDate),
            $timestamp
        );
    }

    /**
     * Get the current configured format template
     */
    public function getFormatTemplate(): string
    {
        return $this->getConfiguredFormat();
    }

    /**
     * Get the configured format from SystemPreferences
     */
    private function getConfiguredFormat(): string
    {
        $preference = $this->systemPreferenceRepository->findByKey(PreferenceKey::INVOICE_NUMBER_FORMAT);
        
        if ($preference === null) {
            return self::DEFAULT_FORMAT;
        }
        
        return $preference->getValue();
    }

    /**
     * Get the next invoice number that would be generated for a given date
     */
    public function previewNextNumber(\DateTimeInterface $issueDate): string
    {
        return $this->generate($issueDate);
    }

    /**
     * Get statistics about invoice numbering for a given period
     */
    public function getNumberingStats(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // This could be extended to provide insights into numbering patterns
        return [
            'format' => $this->getFormatTemplate(),
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'next_number_today' => $this->previewNextNumber(new \DateTime()),
        ];
    }
}