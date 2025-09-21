<?php

declare(strict_types=1);

namespace App\Application\Command\User;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to update an existing user in the system.
 */
final class UpdateUserCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $id,
        
        #[Assert\Length(min: 3, max: 180)]
        public readonly ?string $username = null,
        
        #[Assert\Length(min: 6)]
        public readonly ?string $plainPassword = null,
        
        #[Assert\Type('array')]
        public readonly ?array $roles = null
    ) {
        parent::__construct();
    }
}