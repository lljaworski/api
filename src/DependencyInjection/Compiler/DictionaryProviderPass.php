<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Dictionary\DictionaryRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register dictionary providers with the registry.
 */
class DictionaryProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(DictionaryRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(DictionaryRegistry::class);

        // Register roles provider with admin requirement
        $definition->addMethodCall('registerProvider', [
            new Reference('app.dictionary.provider.roles'),
            ['ROLE_ADMIN']
        ]);
    }
}