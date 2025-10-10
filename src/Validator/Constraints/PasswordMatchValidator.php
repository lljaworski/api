<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PasswordMatchValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PasswordMatch) {
            throw new UnexpectedTypeException($constraint, PasswordMatch::class);
        }

        if (null === $value) {
            return;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            $password = $propertyAccessor->getValue($value, $constraint->passwordField);
            $confirmation = $propertyAccessor->getValue($value, $constraint->confirmationField);
        } catch (\Exception $e) {
            // If we can't access the properties, skip validation
            return;
        }

        // Both values must be present for comparison
        if (null === $password || null === $confirmation) {
            return;
        }

        if ($password !== $confirmation) {
            $this->context->buildViolation($constraint->message)
                ->atPath($constraint->confirmationField)
                ->addViolation();
        }
    }
}