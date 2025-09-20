<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Health;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class HealthProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Health
    {
        $timestamp = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        // Check if this is the ping endpoint
        if ($operation->getName() === 'ping') {
            return new Health(
                status: 'ok',
                timestamp: $timestamp,
                message: 'pong'
            );
        }

        // Full health check response
        return new Health(
            status: 'ok',
            timestamp: $timestamp,
            api: [
                'name' => 'Hello API Platform',
                'version' => '1.0.0',
                'environment' => $this->environment,
            ],
            system: [
                'php_version' => PHP_VERSION,
                'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
                'api_platform_version' => $this->getApiPlatformVersion(),
            ]
        );
    }

    private function getApiPlatformVersion(): string
    {
        try {
            $reflection = new \ReflectionClass(\ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class);
            $composerFile = dirname($reflection->getFileName(), 2) . '/composer.json';
            
            if (file_exists($composerFile)) {
                $composer = json_decode(file_get_contents($composerFile), true);
                return $composer['version'] ?? 'unknown';
            }
        } catch (\Exception) {
            // Fallback if version detection fails
        }

        return 'unknown';
    }
}