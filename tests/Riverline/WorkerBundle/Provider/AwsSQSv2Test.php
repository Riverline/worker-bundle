<?php

namespace Riverline\WorkerBundle\Provider;

class AwsSQSv2Test extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Riverline\WorkerBundle\Provider\AwsSQSv2
     */
    private $provider;

    public function setUp()
    {
        $this->provider = new AwsSQSv2(array(
            'key'    => $GLOBALS['AWS_ACCESS'],
            'secret' => $GLOBALS['AWS_SECRET'],
            'region' => $GLOBALS['AWS_REGION']
        ));
    }

    public function testCreateQueue()
    {
        $newQueue = $this->provider->createQueue("RiverlineWorkerBundleTest_create_v2", array('VisibilityTimeout' => '15'));

        $this->assertTrue($newQueue instanceof \Riverline\WorkerBundle\Queue\Queue);
    }

    public function testPutArray()
    {
        $this->provider->put("RiverlineWorkerBundleTest_create_v2", array('name' => 'Romain'));
    }

    public function testCount()
    {
        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v2");

        $this->assertEquals(1, $count);
    }

    public function testGetArray()
    {
        $workload = $this->provider->get("RiverlineWorkerBundleTest_create_v2");

        $this->assertSame(array('name' => 'Romain'), $workload);
    }

    public function testTimeout()
    {
        $tic = time();

        $this->provider->get("RiverlineWorkerBundleTest_create_v2", 3);

        $this->assertGreaterThanOrEqual(3, time() - $tic);
    }

    public function testMultiPut()
    {
        $workloads = array();
        for($i = 0 ; $i < 10 ; $i++) {
            $workloads[] = "workload$i";
        }

        $this->provider->multiPut("RiverlineWorkerBundleTest_create_v2", $workloads);

        sleep(5);

        $count = $this->provider->count("RiverlineWorkerBundleTest_create_v2");

        $this->assertEquals(10, $count);
    }

    public function testDeleteQueue()
    {
        $deleted = $this->provider->deleteQueue("RiverlineWorkerBundleTest_create_v2");

        $this->assertTrue($deleted);
    }

    public function testGetQueueOptions()
    {
        $queueOptions = $this->provider->getQueueOptions("RiverlineWorkerBundleTest_queue1");

        $this->assertTrue(is_array($queueOptions));
        $this->assertArrayHasKey('MessageRetentionPeriod', $queueOptions);
        $this->assertEquals(10*3600*24, $queueOptions['MessageRetentionPeriod']);
    }

    public function testListQueues()
    {
        $queues = $this->provider->listQueues("RiverlineWorkerBundleTest");

        $this->assertEquals(2, count($queues));
    }

    public function testQueueExists()
    {
        $queueExists = $this->provider->queueExists("RiverlineWorkerBundleTest_queue1");
        $this->assertTrue($queueExists);

        $queueNotExists = $this->provider->queueExists("RiverlineWorkerBundleTest_queueX");
        $this->assertFalse($queueNotExists);
    }

    public function testUpdateQueue()
    {
        $queueUpdated = $this->provider->updateQueue("RiverlineWorkerBundleTest_queue2", array('ReceiveMessageWaitTimeSeconds' => '20'));
        $this->assertTrue($queueUpdated);

        $queueUpdated = $this->provider->updateQueue("RiverlineWorkerBundleTest_queue2", array('ReceiveMessageWaitTimeSeconds' => '0'));
        $this->assertTrue($queueUpdated);
    }

}