<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is a valid Polish NIP (Tax Identification Number).
 * 
 * Polish NIP must be exactly 10 digits.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class PolishNip extends Constraint
{
    public string $message = 'The NIP "{{ value }}" is not a valid Polish NIP number. It must be exactly 10 digits.';
    
    public string $mode = 'strict';
}
