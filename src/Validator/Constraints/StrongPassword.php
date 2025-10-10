<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class StrongPassword extends Constraint
{
    public string $message = 'Password must be at least 8 characters long and contain at least one number and one special character.';
    public string $tooShortMessage = 'Password must be at least 8 characters long.';
    public string $noNumberMessage = 'Password must contain at least one number.';
    public string $noSpecialCharacterMessage = 'Password must contain at least one special character (!@#$%^&*()_+-=[]{}|;:,.<>?).';

    public function __construct(
        array $groups = null,
        mixed $payload = null,
        string $message = null,
        string $tooShortMessage = null,
        string $noNumberMessage = null,
        string $noSpecialCharacterMessage = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->tooShortMessage = $tooShortMessage ?? $this->tooShortMessage;
        $this->noNumberMessage = $noNumberMessage ?? $this->noNumberMessage;
        $this->noSpecialCharacterMessage = $noSpecialCharacterMessage ?? $this->noSpecialCharacterMessage;
    }
}