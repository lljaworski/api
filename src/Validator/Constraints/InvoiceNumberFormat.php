<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates invoice number format strings
 * 
 * Format must contain:
 * - {year} - 4 digit year
 * - {month} - 2 digit month (01-12)
 * - {number} or {number:N} - sequence number with optional padding
 */
#[\Attribute]
class InvoiceNumberFormat extends Constraint
{
    public string $message = 'The invoice number format "{{ format }}" is invalid. Format must contain {year}, {month}, and {number} placeholders.';
    public string $missingPlaceholder = 'The invoice number format must contain the {{ placeholder }} placeholder.';
}
