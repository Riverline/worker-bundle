<?php

namespace Riverline\WorkerBundle\DependencyInjection;

use Riverline\WorkerBundle\Queue\Queue;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $collectionDefinition = $container->getDefinition('riverline.worker_bundle.providers.collection');

        if (isset($config['providers'])) {
            foreach ($config['providers'] as $id => $provider) {
                $serviceId = $this->getAlias().'.provider.'.$id;

                $definition = new Definition($provider['class'], $provider['arguments']);
                $definition->addTag('riverline.worker_bundle.provider', ["name" => $id]);

                $container->setDefinition($serviceId, $definition);
                $collectionDefinition->addMethodCall('addProvider', [$id, new Reference($serviceId)]);
            }
        }

        if (isset($config['queues'])) {
            foreach ($config['queues'] as $id => $queue) {
                $container->setDefinition(
                    $this->getAlias().'.queue.'.$id,
                    new Definition(
                        Queue::class,
                        [
                            $queue['name'],
                            new Reference($this->getAlias().'.provider.'.$queue['provider']),
                        ]
                    )
                );
            }
        }
    }
}
