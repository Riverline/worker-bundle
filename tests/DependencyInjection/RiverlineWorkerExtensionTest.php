<?php

namespace Riverline\WorkerBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use Riverline\WorkerBundle\Provider\Semaphore;
use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class RiverlineWorkerExtensionTest
 * @package Riverline\WorkerBundle\DependencyInjection
 */
class RiverlineWorkerExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $containerBuilder = new ContainerBuilder();
        $config = [
            'riverline_worker' => [
                'providers' => [
                    'semaphore' => [
                        'class' => Semaphore::class,
                    ]
                ],
                'queues' => [
                    'test' => [
                        'provider' => 'semaphore',
                        'name' => 'test'
                    ]
                ]
            ]
        ];

        $extension = new RiverlineWorkerExtension();

        $extension->load($config, $containerBuilder);

        $provider = $containerBuilder->get('riverline_worker.provider.semaphore');

        self::assertInstanceOf(Semaphore::class, $provider);

        $queue = $containerBuilder->get('riverline_worker.queue.test');

        self::assertInstanceOf(Queue::class, $queue);
    }
}
