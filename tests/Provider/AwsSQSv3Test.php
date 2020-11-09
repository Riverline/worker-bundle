<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

class AwsSQSv3Test extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AwsSQSv3
     */
    private $provider;

    public function setUp(): void
    {
        $this->provider = new AwsSQSv3(
            [
                'credentials' => [
                    'key' => "",
                    'secret' => "",
                ],
                'region' => "us-east-1",
                'version' => "latest",
                'endpoint' => 'http://aws:4566',
            ]
        );
    }

    public function testCreateQueue(): void
    {
        $newQueue = $this->provider->createQueue("RiverlineWorkerBundleTest_create_v3", ['VisibilityTimeout' => '15']);
        self::assertInstanceOf(Queue::class, $newQueue);
    }

    public function testPutArray(): void
    {
        $this->provider->put("RiverlineWorkerBundleTest_create_v3", ['name' => 'Romain']);
    }

    public function testCount(): void
    {
        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v3");

        self::assertEquals(1, $count);
    }

    public function testGetArray(): void
    {
        $workload = $this->provider->get("RiverlineWorkerBundleTest_create_v3");

        self::assertSame(['name' => 'Romain'], $workload);
    }

    public function testTimeout(): void
    {
        $tic = time();

        $this->provider->get("RiverlineWorkerBundleTest_create_v3", 3);

        self::assertGreaterThanOrEqual(3, time() - $tic);
    }

    public function testMultiPut(): void
    {
        $workloads = [];
        for ($i = 0; $i < 10; $i++) {
            $workloads[] = "workload$i";
        }

        $this->provider->multiPut("RiverlineWorkerBundleTest_create_v3", $workloads);

        sleep(5);

        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v3");

        self::assertEquals(10, $count);
    }

    public function testDeleteQueue(): void
    {
        $deleted = $this->provider->deleteQueue("RiverlineWorkerBundleTest_create_v3");

        self::assertTrue($deleted);
    }

    public function testGetQueueOptions(): void
    {
        $this->provider->createQueue("RiverlineWorkerBundleTest_queue1", ["VisibilityTimeout" => 60]);
        $queueOptions = $this->provider->getQueueOptions("RiverlineWorkerBundleTest_queue1");

        self::assertTrue(is_array($queueOptions));
        self::assertArrayHasKey('MessageRetentionPeriod', $queueOptions);
        self::assertEquals(345600, $queueOptions['MessageRetentionPeriod']);
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
        $queueUpdated = $this->provider->updateQueue(
            "RiverlineWorkerBundleTest_queue1",
            ['ReceiveMessageWaitTimeSeconds' => '20']
        );
        self::assertTrue($queueUpdated);

        $queueUpdated = $this->provider->updateQueue(
            "RiverlineWorkerBundleTest_queue1",
            ['ReceiveMessageWaitTimeSeconds' => '0']
        );
        self::assertTrue($queueUpdated);
    }
}
