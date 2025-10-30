<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\InvoiceRepository;

class InvoiceNumberGenerator
{
    private const PREFIX = 'FV';
    private const FORMAT = '%s/%s/%s/%04d';

    public function __construct(
        private readonly InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Generate a unique invoice number based on issue date
     * Format: FV/YYYY/MM/NNNN (e.g., FV/2025/10/0001)
     */
    public function generate(\DateTimeInterface $issueDate): string
    {
        $year = $issueDate->format('Y');
        $month = $issueDate->format('m');
        $sequenceNumber = $this->invoiceRepository->getNextSequenceNumber($issueDate);

        return sprintf(
            self::FORMAT,
            self::PREFIX,
            $year,
            $month,
            $sequenceNumber
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
        $pattern = '/^FV\/\d{4}\/\d{2}\/\d{4}$/';
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
        return 'FV/YYYY/MM/NNNN';
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