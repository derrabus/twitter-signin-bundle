<?php

namespace Rabus\Bundle\Twitter\SignInBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rabus_twitter');
        $rootNode
            ->children()
            ->scalarNode('consumer_key')->isRequired()->end()
            ->scalarNode('consumer_secret')->isRequired()->end()
            ->end();

        return $treeBuilder;
    }
}
