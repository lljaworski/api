<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordMatch extends Constraint
{
    public string $message = 'Password confirmation does not match the new password.';
    public string $passwordField = 'newPassword';
    public string $confirmationField = 'passwordConfirmation';

    public function __construct(
        ?string $passwordField = null,
        ?string $confirmationField = null,
        ?array $groups = null,
        mixed $payload = null,
        ?string $message = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->passwordField = $passwordField ?? $this->passwordField;
        $this->confirmationField = $confirmationField ?? $this->confirmationField;
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}