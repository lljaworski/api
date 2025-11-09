<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class InvoiceNumberFormatValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InvoiceNumberFormat) {
            throw new UnexpectedTypeException($constraint, InvoiceNumberFormat::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // Check for required placeholders
        $requiredPlaceholders = ['{year}', '{month}', '{number}'];
        $hasNumber = false;

        foreach ($requiredPlaceholders as $placeholder) {
            if ($placeholder === '{number}') {
                // Check for {number} or {number:N} format
                if (str_contains($value, '{number}') || preg_match('/\{number:\d+\}/', $value)) {
                    $hasNumber = true;
                }
            } elseif (!str_contains($value, $placeholder)) {
                $this->context->buildViolation($constraint->missingPlaceholder)
                    ->setParameter('{{ placeholder }}', $placeholder)
                    ->addViolation();
                return;
            }
        }

        if (!$hasNumber) {
            $this->context->buildViolation($constraint->missingPlaceholder)
                ->setParameter('{{ placeholder }}', '{number}')
                ->addViolation();
        }
    }
}
