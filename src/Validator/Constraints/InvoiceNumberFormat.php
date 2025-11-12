<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class InvoiceNumberFormat extends Constraint
{
    public string $message = 'The invoice number format must contain the required placeholders: {year}, {month}, and {number}.';
    public string $missingYearMessage = 'The invoice number format must contain the {year} placeholder.';
    public string $missingMonthMessage = 'The invoice number format must contain the {month} placeholder.';
    public string $missingNumberMessage = 'The invoice number format must contain the {number} placeholder.';
    public string $notStringMessage = 'The invoice number format must be a string.';

    public function __construct(
        array $groups = null,
        mixed $payload = null,
        string $message = null,
        string $missingYearMessage = null,
        string $missingMonthMessage = null,
        string $missingNumberMessage = null,
        string $notStringMessage = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->missingYearMessage = $missingYearMessage ?? $this->missingYearMessage;
        $this->missingMonthMessage = $missingMonthMessage ?? $this->missingMonthMessage;
        $this->missingNumberMessage = $missingNumberMessage ?? $this->missingNumberMessage;
        $this->notStringMessage = $notStringMessage ?? $this->notStringMessage;
    }
}
