<?php

namespace Riverline\WorkerBundle\Provider;

use PHPUnit\Framework\TestCase;
use Riverline\WorkerBundle\Provider\File as FileProvider;

/**
 * Class FileTest
 * @package Riverline\WorkerBundle\Provider
 */
class FileTest extends TestCase
{

    /**
     * @var \Riverline\WorkerBundle\Provider\File
     */
    private $provider;

    public function setUp()
    {
        $this->provider = new FileProvider();
    }

    public function testCreateQueue()
    {
        $newQueue = $this->provider->createQueue("RiverlineWorkerBundleTest_file_create");

        $this->assertTrue($newQueue instanceof \Riverline\WorkerBundle\Queue\Queue);
    }

    public function testPutArray()
    {
        $this->provider->put("RiverlineWorkerBundleTest_file_create", array('name' => 'Romain'));
    }

    public function testCount()
    {
        $count = $this->provider->count("RiverlineWorkerBundleTest_file_create");

        $this->assertEquals(1, $count);
    }

    public function testGetArray()
    {
        $workload = $this->provider->get("RiverlineWorkerBundleTest_file_create");

        $this->assertSame(array('name' => 'Romain'), $workload);
    }

    public function testMultiPut()
    {
        $workloads = array();
        for($i = 0 ; $i < 10 ; $i++) {
            $workloads[] = "workload$i";
        }

        $this->provider->multiPut("RiverlineWorkerBundleTest_file_create", $workloads);

        sleep(5);

        $count = $this->provider->count("RiverlineWorkerBundleTest_file_create");

        $this->assertEquals(10, $count);
    }

    public function testDeleteQueue()
    {
        $deleted = $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_create");

        $this->assertTrue($deleted);
    }

    public function testListQueues()
    {
        $this->provider->createQueue("RiverlineWorkerBundleTest_file_listqueue1");
        $this->provider->createQueue("RiverlineWorkerBundleTest_file_listqueue2");

        $queues = $this->provider->listQueues("RiverlineWorkerBundleTest");

        $this->assertEquals(2, count($queues));

        $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_listqueue1");
        $this->provider->deleteQueue("RiverlineWorkerBundleTest_file_listqueue2");
    }

    public function testQueueExists()
    {
        $queueName = "RiverlineWorkerBundleTest_file_queue1";
        $this->provider->createQueue($queueName);

        $queueExists = $this->provider->queueExists($queueName);
        $this->assertTrue($queueExists);

        $this->provider->deleteQueue($queueName);

        $queueNotExists = $this->provider->queueExists("RiverlineWorkerBundleTest_file_queueX");
        $this->assertFalse($queueNotExists);
    }

}
