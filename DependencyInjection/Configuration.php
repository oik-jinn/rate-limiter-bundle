<?php

namespace RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tnc_rate_limit');

        $rootNode
            ->children()
                ->arrayNode('limiters')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('user_id')
                                ->prototype('array')
                                    ->children()
                                        ->integerNode('decay')
                                            ->isRequired()
                                        ->end()
                                        ->integerNode('limit')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('algorithm')
                                            ->isRequired()
                                            ->validate()
                                            ->ifNotInArray(array('counter', 'rolling window', 'leaky bucket', 'token bucket'))
                                            ->thenInvalid('Invalid algorithm type %s')
                                            ->end()
                                        ->end()
                                        ->booleanNode('is_resource_unique')->end()
                                        ->booleanNode('flush_cache_by_day')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('ip')
                                ->prototype('array')
                                    ->children()
                                        ->integerNode('decay')
                                            ->isRequired()
                                        ->end()
                                        ->integerNode('limit')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('algorithm')
                                            ->isRequired()
                                            ->validate()
                                            ->ifNotInArray(array('counter', 'rolling window', 'leaky bucket', 'token bucket'))
                                            ->thenInvalid('Invalid algorithm type %s')
                                            ->end()
                                        ->end()
                                        ->booleanNode('is_resource_unique')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('connection')
                    ->isRequired()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
