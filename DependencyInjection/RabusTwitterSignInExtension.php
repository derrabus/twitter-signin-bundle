<?php

namespace Rabus\Bundle\Twitter\SignInBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class RabusTwitterSignInExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);

        $factoryDefinition = new Definition(
            'Rabus\\Bundle\\Twitter\\SignInBundle\\Service\\ConnectionFactory',
            array($config['consumer_key'], $config['consumer_secret'])
        );
        $factoryDefinition
            ->setPublic(false)
            ->addMethodCall(
                'setLogger',
                array(new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            );
        $container->setDefinition(
            'rabus.twitter.connection_factory',
            $factoryDefinition
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
