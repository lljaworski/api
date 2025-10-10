<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        // null and empty values are valid (use NotBlank constraint for required validation)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Check minimum length (8 characters)
        if (strlen($value) < 8) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->addViolation();
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $this->context->buildViolation($constraint->noNumberMessage)
                ->addViolation();
            return;
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $value)) {
            $this->context->buildViolation($constraint->noSpecialCharacterMessage)
                ->addViolation();
            return;
        }
    }
}