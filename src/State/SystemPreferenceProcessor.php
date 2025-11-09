<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Command\SystemPreference\CreateSystemPreferenceCommand;
use App\Application\Command\SystemPreference\DeleteSystemPreferenceCommand;
use App\Application\Command\SystemPreference\UpdateSystemPreferenceCommand;
use App\Entity\SystemPreference;
use App\Enum\PreferenceKey;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @implements ProcessorInterface<SystemPreference, SystemPreference|void>
 */
final class SystemPreferenceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof SystemPreference) {
            return $data;
        }

        // Handle delete operations
        if ($operation instanceof DeleteOperationInterface) {
            $command = new DeleteSystemPreferenceCommand($data->getId());
            $this->commandBus->dispatch($command);
            return null;
        }

        // Handle create and update operations
        $isUpdate = $data->getId() !== null;
        
        if ($isUpdate) {
            // For updates, create an update command with only the fields that should be updated
            $command = new UpdateSystemPreferenceCommand(
                id: $data->getId(),
                preferenceKey: $this->extractUpdatedPreferenceKey($data, $context),
                value: $this->extractUpdatedValue($data, $context)
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        } else {
            // For creation, create a create command
            $command = new CreateSystemPreferenceCommand(
                preferenceKey: $data->getPreferenceKey(),
                value: $data->getValue()
            );
            
            $envelope = $this->commandBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            
            return $handledStamp->getResult();
        }
    }
    
    /**
     * Extract preference key from request if it was explicitly provided for update.
     */
    private function extractUpdatedPreferenceKey(SystemPreference $data, array $context): ?PreferenceKey
    {
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true);
            
            if (array_key_exists('preferenceKey', $requestData)) {
                return PreferenceKey::from($requestData['preferenceKey']);
            }
        }
        
        return null; // Don't update key if not provided
    }

    /**
     * Extract value from request if it was explicitly provided for update.
     */
    private function extractUpdatedValue(SystemPreference $data, array $context): mixed
    {
        if (isset($context['request'])) {
            $request = $context['request'];
            $requestData = json_decode($request->getContent(), true);
            
            if (array_key_exists('value', $requestData)) {
                return $requestData['value'];
            }
        }
        
        return null; // Don't update value if not provided
    }
}
