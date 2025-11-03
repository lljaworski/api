<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator for Polish NIP (Tax Identification Number).
 * 
 * Validates that the NIP is exactly 10 digits.
 */
class PolishNipValidator extends ConstraintValidator
{
    private const NIP_LENGTH = 10;
    
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PolishNip) {
            throw new UnexpectedTypeException($constraint, PolishNip::class);
        }

        // Null and empty strings are valid (use NotBlank constraint separately if needed)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Remove any spaces, dashes, or other separators
        $cleanValue = preg_replace('/[^0-9]/', '', $value);

        // Check if it's exactly 10 digits
        if (strlen($cleanValue) !== self::NIP_LENGTH) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
            
            return;
        }

        // Check if all characters are digits
        if (!ctype_digit($cleanValue)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
