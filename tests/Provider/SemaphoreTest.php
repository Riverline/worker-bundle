<?php

namespace Riverline\WorkerBundle\Provider;

class SemaphoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Semaphore
     */
    private $provider;

    public function setUp(): void
    {
        // clean
        $this->provider = new Semaphore(['id' => rand()]);
    }

    public function testPutArray(): void
    {
        $this->provider->put('test', ['name' => 'Romain']);
    }

    public function testCount(): void
    {
        $count = $this->provider->count('test');

        self::assertEquals(1, $count);
    }

    public function testGetArray(): void
    {
        $workload = $this->provider->get('test');

        self::assertSame(['name' => 'Romain'], $workload);
    }
}
