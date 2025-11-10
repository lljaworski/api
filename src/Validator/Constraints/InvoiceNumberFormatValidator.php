<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class InvoiceNumberFormatValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InvoiceNumberFormat) {
            throw new UnexpectedTypeException($constraint, InvoiceNumberFormat::class);
        }

        // null and empty values are valid (use NotBlank constraint for required validation)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            $this->context->buildViolation($constraint->notStringMessage)
                ->addViolation();
            return;
        }

        // Check for required placeholder: {year}
        if (!str_contains($value, '{year}')) {
            $this->context->buildViolation($constraint->missingYearMessage)
                ->addViolation();
            return;
        }

        // Check for required placeholder: {month}
        if (!str_contains($value, '{month}')) {
            $this->context->buildViolation($constraint->missingMonthMessage)
                ->addViolation();
            return;
        }

        // Check for required placeholder: {number}
        if (!str_contains($value, '{number}')) {
            $this->context->buildViolation($constraint->missingNumberMessage)
                ->addViolation();
            return;
        }
    }
}
