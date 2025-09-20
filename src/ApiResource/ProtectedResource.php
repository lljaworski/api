<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/protected',
            provider: 'App\State\ProtectedProvider'
        )
    ],
    normalizationContext: ['groups' => ['protected:read']]
)]
class ProtectedResource
{
    #[Groups(['protected:read'])]
    public string $message;

    #[Groups(['protected:read'])]
    public string $user;

    #[Groups(['protected:read'])]
    public string $timestamp;

    #[Groups(['protected:read'])]
    public array $roles;

    public function __construct(string $message, string $user, string $timestamp, array $roles)
    {
        $this->message = $message;
        $this->user = $user;
        $this->timestamp = $timestamp;
        $this->roles = $roles;
    }
}