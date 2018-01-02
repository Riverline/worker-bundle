<?php

namespace Riverline\WorkerBundle\Provider;

use PHPUnit\Framework\TestCase;

/**
 * Class PRedisTest
 * @package Riverline\WorkerBundle\Provider
 */
class PRedisTest extends TestCase
{
    /**
     * @var \Riverline\WorkerBundle\Queue\Queue
     */
    private $queue;

    /**
     *
     */
    public function setUp()
    {
        // clean
        $this->queue = new \Riverline\WorkerBundle\Queue\Queue(
            'Test',
            new PRedis(array(
                'host' => "redis"
            ))
        );

        $this->markTestSkipped("Tests should be fixed");
    }

    public function testPutArray()
    {
        $this->queue->put(array('name' => 'Romain'));
    }

    public function testCount()
    {
        $count = $this->queue->count();

        $this->assertEquals(1, $count);
    }

    public function testGetArray()
    {
        $workload = $this->queue->get();

        $this->assertSame(array('name' => 'Romain'), $workload);
    }

    public function testTimeout()
    {
        $tic = time();

        $this->queue->get(5);

        $this->assertGreaterThan(5, time() - $tic);
    }

}
