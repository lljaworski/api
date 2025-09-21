<?php

declare(strict_types=1);

namespace App\Application\Command\User;

use App\Application\Command\AbstractCommand;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command to soft delete a user from the system.
 */
final class DeleteUserCommand extends AbstractCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $id
    ) {
        parent::__construct();
    }
}