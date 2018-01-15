<?php

namespace Riverline\WorkerBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Riverline\WorkerBundle\Provider\Collection\ProviderCollection;
use Riverline\WorkerBundle\Provider\Semaphore;
use Riverline\WorkerBundle\Queue\Queue;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RiverlineWorkerExtensionTest
 * @package Riverline\WorkerBundle\DependencyInjection
 */
class RiverlineWorkerExtensionTest extends AbstractExtensionTestCase
{
    /**
     *
     */
    public function testShouldLoad()
    {
        $this->load(
            [
                'providers' => [
                    'semaphore' => [
                        'class' => Semaphore::class,
                    ],
                ],
                'queues'    => [
                    'test' => [
                        'provider' => 'semaphore',
                        'name'     => 'test',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasService('riverline_worker.provider.semaphore', Semaphore::class);
        $this->assertContainerBuilderHasService('riverline_worker.queue.test', Queue::class);
        $this->assertContainerBuilderHasService('riverline.worker_bundle.providers.collection', ProviderCollection::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'riverline.worker_bundle.providers.collection',
            'addProvider',
            [
                'semaphore',
                new Reference('riverline_worker.provider.semaphore'),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return [
            new RiverlineWorkerExtension(),
        ];
    }
}
