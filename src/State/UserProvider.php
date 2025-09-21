<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * @implements ProviderInterface<User>
 */
final class UserProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Handle single user retrieval
        if (isset($uriVariables['id'])) {
            $user = $this->userRepository->findActiveById((int) $uriVariables['id']);
            
            if (!$user) {
                return null;
            }

            return $user;
        }

        // Handle user collection
        return $this->userRepository->findAllActive();
    }
}