<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\HealthProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/health',
            provider: HealthProvider::class,
            name: 'health_check'
        ),
        new Get(
            uriTemplate: '/ping',
            provider: HealthProvider::class,
            name: 'ping'
        ),
    ],
    shortName: 'Health',
    description: 'API Health Check endpoints for monitoring and load balancers'
)]
class Health
{
    public string $status;
    public string $timestamp;
    public ?array $api = null;
    public ?array $system = null;
    public ?string $message = null;

    public function __construct(
        string $status,
        string $timestamp,
        ?array $api = null,
        ?array $system = null,
        ?string $message = null,
    ) {
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->api = $api;
        $this->system = $system;
        $this->message = $message;
    }
}