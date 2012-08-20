<?php

namespace Riverline\WorkerBundle\Provider;

class SemaphoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Semaphore
     */
    private $provider;

    public function setUp()
    {
        // clean
        $this->provider = new Semaphore(array('id' => rand()));
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