<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ProtectedResource;
use Symfony\Bundle\SecurityBundle\Security;

final class ProtectedProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProtectedResource
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            throw new \RuntimeException('User must be authenticated');
        }

        return new ProtectedResource(
            message: 'This is a protected endpoint - you are authenticated!',
            user: $user->getUserIdentifier(),
            timestamp: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            roles: $user->getRoles()
        );
    }
}