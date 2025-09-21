<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\User\CreateUserCommand;
use App\Application\Command\User\DeleteUserCommand;
use App\Application\Command\User\UpdateUserCommand;
use App\Entity\User;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @implements ProcessorInterface<User, User|void>
 */
final class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $data;
        }

        // Handle delete operations (soft delete)
        if ($operation instanceof DeleteOperationInterface) {
            $command = new DeleteUserCommand($data->getId());
            $this->commandBus->dispatch($command);
            return null;
        }

        // Handle create and update operations
        $isUpdate = $data->getId() !== null;
        
        if ($isUpdate) {
            // For updates, create an update command with only the fields that should be updated
            $command = new UpdateUserCommand(
                id: $data->getId(),
                username: $this->extractUpdatedUsername($data, $context),
                plainPassword: $this->extractPlainPassword($data, $context),
                roles: $this->extractUpdatedRoles($data, $context)
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        } else {
            // For creation, create a create command
            $command = new CreateUserCommand(
                username: $data->getUsername(),
                plainPassword: $this->extractPlainPassword($data, $context),
                roles: $data->getRoles()
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        }
    }
    
    /**
     * Extract plain password from request context or entity.
     * In API Platform, the plain password is usually available in the request data.
     */
    private function extractPlainPassword(User $data, array $context): ?string
    {
        // Try to get plain password from the request data
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true);
            
            if (isset($requestData['password'])) {
                return $requestData['password'];
            }
            
            if (isset($requestData['plainPassword'])) {
                return $requestData['plainPassword'];
            }
        }
        
        // For updates, if no password is provided, return null (don't update password)
        return null;
    }

    /**
     * Extract username from request if it was explicitly provided for update.
     */
    private function extractUpdatedUsername(User $data, array $context): ?string
    {
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true);
            
            if (array_key_exists('username', $requestData)) {
                return $requestData['username'];
            }
        }
        
        return null; // Don't update username if not provided
    }

    /**
     * Extract roles from request if they were explicitly provided for update.
     */
    private function extractUpdatedRoles(User $data, array $context): ?array
    {
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true);
            
            if (array_key_exists('roles', $requestData)) {
                return $requestData['roles'];
            }
        }
        
        return null; // Don't update roles if not provided
    }
}