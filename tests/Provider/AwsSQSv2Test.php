<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class AwsSQSv2Test
 * @package Riverline\WorkerBundle\Provider
 * @deprecated
 */
class AwsSQSv2Test extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AwsSQSv2
     */
    private $provider;

    public function setUp(): void
    {
        self::markTestSkipped("SDK v2 is used");
        $this->provider = new AwsSQSv2(
            [
                'key' => "key",
                'secret' => "secret",
                'region' => "us-east-1",
                'version' => "latest",
                'endpoint' => 'http://aws:4566',
            ]
        );
    }

    public function testCreateQueue(): void
    {
        $newQueue = $this->provider->createQueue("RiverlineWorkerBundleTest_create_v2", ['VisibilityTimeout' => '15']);

        self::assertInstanceOf(Queue::class, $newQueue);
    }

    public function testPutArray(): void
    {
        $this->provider->put("RiverlineWorkerBundleTest_create_v2", ['name' => 'Romain']);
    }

    public function testCount(): void
    {
        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v2");

        self::assertEquals(1, $count);
    }

    public function testGetArray(): void
    {
        $workload = $this->provider->get("RiverlineWorkerBundleTest_create_v2");

        self::assertSame(['name' => 'Romain'], $workload);
    }

    public function testTimeout(): void
    {
        $tic = time();

        $this->provider->get("RiverlineWorkerBundleTest_create_v2", 3);

        self::assertGreaterThanOrEqual(3, time() - $tic);
    }

    public function testMultiPut(): void
    {
        $workloads = [];
        for ($i = 0; $i < 10; $i++) {
            $workloads[] = "workload$i";
        }

        $this->provider->multiPut("RiverlineWorkerBundleTest_create_v2", $workloads);

        sleep(5);

        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v2");

        self::assertEquals(10, $count);
    }

    public function testDeleteQueue(): void
    {
        $deleted = $this->provider->deleteQueue("RiverlineWorkerBundleTest_create_v2");

        self::assertTrue($deleted);
    }

    public function testGetQueueOptions(): void
    {
        $this->provider->createQueue("RiverlineWorkerBundleTest_queue1", ["VisibilityTimeout" => 60]);
        $queueOptions = $this->provider->getQueueOptions("RiverlineWorkerBundleTest_queue1");

        self::assertTrue(is_array($queueOptions));
        self::assertArrayHasKey('VisibilityTimeout', $queueOptions);
        self::assertEquals(60, $queueOptions['VisibilityTimeout']);
    }

    public function testListQueues(): void
    {
        $queues = $this->provider->listQueues("RiverlineWorkerBundleTest");

        self::assertCount(1, $queues);
    }

    public function testQueueExists(): void
    {
        $queueExists = $this->provider->queueExists("RiverlineWorkerBundleTest_queue1");
        self::assertTrue($queueExists);

        $queueNotExists = $this->provider->queueExists("RiverlineWorkerBundleTest_queueX");
        self::assertFalse($queueNotExists);
    }

    public function testUpdateQueue(): void
    {
        $this->provider->createQueue("RiverlineWorkerBundleTest_queue2", ["VisibilityTimeout" => 60]);
        $queueUpdated = $this->provider->updateQueue(
            "RiverlineWorkerBundleTest_queue2",
            ['ReceiveMessageWaitTimeSeconds' => '20']
        );
        self::assertTrue($queueUpdated);

        $queueUpdated = $this->provider->updateQueue(
            "RiverlineWorkerBundleTest_queue2",
            ['ReceiveMessageWaitTimeSeconds' => '0']
        );
        self::assertTrue($queueUpdated);
    }

    public function testMessagesPerRequest(): void
    {
        $provider = new AwsSQSv2(
            [
                'key' => "key",
                'secret' => "secret",
                'region' => "us-east-1",
                'version' => "latest",
                'endpoint' => 'http://aws:4566',
            ], 5
        );

        $queue = $provider->createQueue("RiverlineWorkerBundleTest_create_multi", ['VisibilityTimeout' => '15']);

        $queue->multiPut(['a', 'b', 'c', 'd']);

        self::assertNotNull($queue->get());
        self::assertNotNull($queue->get());
        self::assertNotNull($queue->get());

        self::assertSame(0, $queue->count());

        // Destruction
        unset($provider, $queue);

        self::assertSame(1, $this->provider->count('RiverlineWorkerBundleTest_create_multi'));
    }
}
