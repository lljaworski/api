<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CommandInterface;

/**
 * Interface for command handlers in the CQRS pattern.
 * Command handlers process commands and execute business logic.
 * 
 * @template T of CommandInterface
 */
interface CommandHandlerInterface
{
    /**
     * Handles the given command.
     * 
     * @param T $command
     * @return mixed The result of the command execution
     */
    public function __invoke(CommandInterface $command): mixed;
}