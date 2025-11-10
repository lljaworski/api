<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\SystemPreference;
use App\Enum\PreferenceKey;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidSystemPreferenceValueValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidSystemPreferenceValue) {
            throw new UnexpectedTypeException($constraint, ValidSystemPreferenceValue::class);
        }

        if (!$value instanceof SystemPreference) {
            throw new UnexpectedTypeException($value, SystemPreference::class);
        }

        $preferenceKey = $value->getPreferenceKey();
        $preferenceValue = $value->getValue();

        // Validate invoice number format
        if ($preferenceKey === PreferenceKey::INVOICE_NUMBER_FORMAT) {
            $this->validateInvoiceNumberFormat($preferenceValue);
        }
    }

    private function validateInvoiceNumberFormat(mixed $value): void
    {
        // null and empty values are not allowed for invoice number format
        if (null === $value || '' === $value) {
            $this->context->buildViolation('The invoice number format cannot be empty.')
                ->atPath('value')
                ->addViolation();
            return;
        }

        if (!is_string($value)) {
            $this->context->buildViolation('The invoice number format must be a string.')
                ->atPath('value')
                ->addViolation();
            return;
        }

        // Check for required placeholder: {year}
        if (!str_contains($value, '{year}')) {
            $this->context->buildViolation('The invoice number format must contain the {year} placeholder.')
                ->atPath('value')
                ->addViolation();
            return;
        }

        // Check for required placeholder: {month}
        if (!str_contains($value, '{month}')) {
            $this->context->buildViolation('The invoice number format must contain the {month} placeholder.')
                ->atPath('value')
                ->addViolation();
            return;
        }

        // Check for required placeholder: {number}
        if (!str_contains($value, '{number}')) {
            $this->context->buildViolation('The invoice number format must contain the {number} placeholder.')
                ->atPath('value')
                ->addViolation();
            return;
        }
    }
}
