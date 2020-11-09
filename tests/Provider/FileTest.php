<?php

namespace Riverline\WorkerBundle\Provider;

use PHPUnit\Framework\TestCase;
use Riverline\WorkerBundle\Provider\File as FileProvider;
use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class FileTest
 * @package Riverline\WorkerBundle\Provider
 */
class FileTest extends TestCase
{

    /**
     * @var File
     */
    private $provider;

    public function setUp(): void
    {
        $this->provider = new FileProvider();
    }

    public function testCreateQueue(): void
    {
        $newQueue = $this->provider->createQueue("RiverlineWorkerBundleTest_file_create");

        self::assertInstanceOf(Queue::class, $newQueue);
    }

    public function testPutArray(): void
    {
        $this->provider->put("RiverlineWorkerBundleTest_file_create", ['name' => 'Romain']);
    }

    public function testCount(): void
    {
        $count = $this->provider->count("RiverlineWorkerBundleTest_file_create");

        self::assertEquals(1, $count);
    }

    public function testGetArray(): void
    {
        $workload = $this->provider->get("RiverlineWorkerBundleTest_file_create");

        self::assertSame(['name' => 'Romain'], $workload);
    }

    public function testMultiPut(): void
    {
        $workloads = [];
        for ($i = 0; $i < 10; $i++) {
            $workloads[] = "workload$i";
        }

        $this->provider->multiPut("RiverlineWorkerBundleTest_file_create", $workloads);

        sleep(5);

        $count = $this->provider->count("RiverlineWorkerBundleTest_file_create");

        self::assertEquals(10, $count);
    }

    public function testDeleteQueue(): void
    {
        $deleted = $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_create");

        self::assertTrue($deleted);
    }

    public function testListQueues(): void
    {
        $this->provider->createQueue("RiverlineWorkerBundleTest_file_listqueue1");
        $this->provider->createQueue("RiverlineWorkerBundleTest_file_listqueue2");

        $queues = $this->provider->listQueues("RiverlineWorkerBundleTest");

        self::assertCount(2, $queues);

        $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_listqueue1");
        $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_listqueue2");
    }

    public function testQueueExists(): void
    {
        $queueName = "RiverlineWorkerBundleTest_file_queue1";
        $this->provider->createQueue($queueName);

        $queueExists = $this->provider->queueExists($queueName);
        self::assertTrue($queueExists);

        $this->provider->deleteQueue($queueName);

        $queueNotExists = $this->provider->queueExists("RiverlineWorkerBundleTest_file_queueX");
        self::assertFalse($queueNotExists);
    }

}
