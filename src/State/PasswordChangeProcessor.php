<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\PasswordChange;
use App\Application\Command\User\ChangePasswordCommand;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @implements ProcessorInterface<PasswordChange, PasswordChange>
 */
final class PasswordChangeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof PasswordChange) {
            throw new BadRequestHttpException('Invalid data provided.');
        }

        // Validate that userId is provided
        if (!$data->userId) {
            throw new BadRequestHttpException('User ID is required.');
        }

        // Security check: Users can only change their own password, admins can change any password
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('Invalid user type.');
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $isOwnPassword = $currentUser->getId() === $data->userId;

        if (!$isAdmin && !$isOwnPassword) {
            throw new AccessDeniedHttpException('You can only change your own password.');
        }

        // Create and dispatch the command
        $command = new ChangePasswordCommand(
            userId: $data->userId,
            oldPassword: $data->oldPassword,
            newPassword: $data->newPassword
        );

        $this->commandBus->dispatch($command);

        // Return success response
        return new PasswordChange(
            message: 'Password changed successfully.'
        );
    }
}