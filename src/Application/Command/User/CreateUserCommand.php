<?php

declare(strict_types=1);

namespace App\Application\Command\User;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to create a new user in the system.
 */
final class CreateUserCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 180)]
        public readonly string $username,
        
        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $plainPassword,
        
        #[Assert\NotNull]
        #[Assert\Type('array')]
        public readonly array $roles = ['ROLE_USER']
    ) {
        parent::__construct();
    }
}