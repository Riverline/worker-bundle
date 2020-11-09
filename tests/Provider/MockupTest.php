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

    public function setUp(): void
    {
        $this->provider = new Mockup();
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
