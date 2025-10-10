<?php

declare(strict_types=1);

namespace App\Application\Command\User;

use App\Application\Command\CommandInterface;

final readonly class ChangePasswordCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public string $oldPassword,
        public string $newPassword
    ) {
    }
}