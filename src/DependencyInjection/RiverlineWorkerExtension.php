<?php

namespace Riverline\WorkerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class RiverlineWorkerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['providers'])) {
            foreach ($config['providers'] as $id => $provider) {
                $container->setDefinition(
                    $this->getAlias().'.provider.'.$id,
                    new Definition($provider['class'], $provider['arguments'])
                );
            }
        }

        if (isset($config['queues'])) {
            foreach ($config['queues'] as $id => $queue) {
                $container->setDefinition(
                    $this->getAlias().'.queue.'.$id,
                    new Definition('Riverline\WorkerBundle\Queue\Queue', array(
                        $queue['name'],
                        new Reference($this->getAlias().'.provider.'.$queue['provider'])
                    ))
                );
            }
        }
    }
}
