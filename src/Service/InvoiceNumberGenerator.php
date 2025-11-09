<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InvoiceRepository;
use App\Repository\InvoiceSettingsRepository;

class InvoiceNumberGenerator
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly InvoiceSettingsRepository $settingsRepository
    ) {}

    /**
     * Generate a unique invoice number based on issue date and configured format
     */
    public function generate(\DateTimeInterface $issueDate): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $format = $settings->getNumberFormat();
        
        return $this->generateFromFormat($format, $issueDate);
    }

    /**
     * Generate invoice number from a specific format template
     */
    public function generateFromFormat(string $format, \DateTimeInterface $issueDate): string
    {
        $year = $issueDate->format('Y');
        $month = $issueDate->format('m');
        $sequenceNumber = $this->invoiceRepository->getNextSequenceNumber($issueDate);

        // Replace placeholders
        $number = str_replace('{year}', $year, $format);
        $number = str_replace('{month}', $month, $number);
        
        // Handle {number} or {number:N} placeholder
        if (preg_match('/\{number:(\d+)\}/', $number, $matches)) {
            $padding = (int) $matches[1];
            $formattedSequence = str_pad((string) $sequenceNumber, $padding, '0', STR_PAD_LEFT);
            $number = preg_replace('/\{number:\d+\}/', $formattedSequence, $number);
        } else {
            $number = str_replace('{number}', (string) $sequenceNumber, $number);
        }

        return $number;
    }

    /**
     * Generate the next available number for the current date
     */
    public function generateForToday(): string
    {
        return $this->generate(new \DateTime());
    }

    /**
     * Validate if an invoice number follows the expected format from settings
     */
    public function isValidFormat(string $number): bool
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        $format = $settings->getNumberFormat();
        
        // Create a regex pattern from the format template
        $pattern = preg_quote($format, '/');
        $pattern = str_replace('\{year\}', '\d{4}', $pattern);
        $pattern = str_replace('\{month\}', '\d{2}', $pattern);
        $pattern = preg_replace('/\\\{number(?::\d+)?\\\}/', '\d+', $pattern);
        
        return preg_match('/^' . $pattern . '$/', $number) === 1;
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

        $parts = explode('/', $number);
        
        return [
            'prefix' => $parts[0],
            'year' => (int) $parts[1],
            'month' => (int) $parts[2],
            'sequence' => (int) $parts[3]
        ];
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
            self::PREFIX,
            $issueDate->format('Y'),
            $issueDate->format('m'),
            $this->invoiceRepository->getNextSequenceNumber($issueDate),
            $timestamp
        );
    }

    /**
     * Get the current format template for display purposes
     */
    public function getFormatTemplate(): string
    {
        $settings = $this->settingsRepository->getOrCreateSettings();
        return $settings->getNumberFormat();
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