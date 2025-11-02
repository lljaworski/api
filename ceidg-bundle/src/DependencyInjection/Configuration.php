<?php

declare(strict_types=1);

namespace LukaszJaworski\CeidgBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ceidg');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_url')
                    ->defaultValue('https://dane.biznes.gov.pl/api/ceidg/v2')
                    ->info('CEIDG API base URL')
                ->end()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('CEIDG API key for authentication')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
