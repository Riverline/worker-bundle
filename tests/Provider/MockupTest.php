<?php

namespace Riverline\WorkerBundle\Provider;

use PHPUnit\Framework\TestCase;

/**
 * Class MockupTest
 * @package Riverline\WorkerBundle\Provider
 */
class MockupTest extends TestCase
{
    /**
     * @var Semaphore
     */
    private $provider;

    public function setUp()
    {
        $this->provider = new Mockup();
    }

    public function testPutArray()
    {
        $this->provider->put('test', array('name' => 'Romain'));
    }

    public function testCount()
    {
        $count = $this->provider->count('test');

        $this->assertEquals(1, $count);
    }

    public function testGetArray()
    {
        $workload = $this->provider->get('test');

        $this->assertSame(array('name' => 'Romain'), $workload);
    }
}
