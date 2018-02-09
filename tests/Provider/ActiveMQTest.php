<?php

namespace Riverline\WorkerBundle\Provider;

use PHPUnit\Framework\TestCase;
use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class ActiveMQTest
 * @package Riverline\WorkerBundle\Provider
 */
class ActiveMQTest extends TestCase
{
    /**
     * @var \Riverline\WorkerBundle\Provider\ActiveMQ
     */
    private $provider;

    /**
     *
     */
    public function setUp()
    {
        $this->provider = new ActiveMQ(
            "tcp://activemq:61613",
            "user",
            "password",
            false,
            true,
            true
        );
    }

    /**
     *
     */
    public function testCount()
    {
        $this->emptyQueue("activemq_queue_empty");
        $this->assertSame(0, $this->provider->count("activemq_queue_empty"));

        for ($i = 0; $i < 2; $i++) {
            $this->provider->put("activemq_queue_empty", "test".$i);
        }

        $this->assertSame(2, $this->provider->count("activemq_queue_empty"));
    }

    /**
     *
     */
    public function testCreateQueueWithSpecialCharacters()
    {
        $this->provider->put("bad_queue name", "bad_queue name");
        $this->assertSame("bad_queue name", $this->provider->get("bad_queue name", 1));

        $this->provider->put("bad_queue namé", "bad_queue namé");
        $this->assertSame("bad_queue namé", $this->provider->get("bad_queue namé", 1));

        $this->provider->put("bad_queue %%%", "bad_queue %%%");
        $this->assertSame("bad_queue %%%", $this->provider->get("bad_queue %%%", 1));
    }

    /**
     *
     */
    public function testCreateQueue()
    {
        $newQueue = $this->provider->createQueue("activemq_create_queue");

        $this->assertTrue($newQueue instanceof Queue);
    }

    /**
     *
     */
    public function testGetNoFrame()
    {
        $this->assertNull($this->provider->get("activemq_no_frame", 1));
    }

    /**
     *
     */
    public function testGetOnMultipleQueues()
    {
        $this->provider->put("activemq_get_1", "message1");
        $this->provider->put("activemq_get_2", "message2");

        $this->assertSame("message1", $this->provider->get("activemq_get_1", 1));
        $this->assertSame("message2", $this->provider->get("activemq_get_2", 1));
    }

    /**
     *
     */
    public function testMultiPut()
    {
        $this->emptyQueue("activemq_multi_put");
        $this->provider->multiPut("activemq_multi_put", [
            "message1",
            "message2",
            ["foo" => "bar"],
            new \stdClass(),
        ]);

        $this->assertSame("message1", $this->provider->get("activemq_multi_put", 1));
        $this->assertSame("message2", $this->provider->get("activemq_multi_put", 1));
        $this->assertSame(["foo" => "bar"], $this->provider->get("activemq_multi_put", 1));
        $this->assertInstanceOf(\stdClass::class, $this->provider->get("activemq_multi_put", 1));
        $this->assertNull($this->provider->get("activemq_multi_put", 1));
    }

    /**
     *
     */
    public function testPutAndGet()
    {
        $this->emptyQueue("activemq_put_get");

        $this->provider->put("activemq_put_get", "coucou");
        $this->assertSame("coucou", $this->provider->get("activemq_put_get", 1));
    }

    /**
     *
     */
    public function testRemainingCount()
    {
        $this->emptyQueue("activemq_remaining_count");

        $this->provider->put("activemq_remaining_count", "coucou");
        $this->provider->put("activemq_remaining_count", "coucou2");
        $this->provider->put("activemq_remaining_count", "coucou3");

        $this->assertSame("coucou", $this->provider->get("activemq_remaining_count", 1));
        $this->assertSame(2, $this->provider->count("activemq_remaining_count"));

        $this->assertSame("coucou2", $this->provider->get("activemq_remaining_count", 1));
        $this->assertSame(1, $this->provider->count("activemq_remaining_count"));
    }

    /**
     *
     */
    public function testChangeQueue()
    {
        $this->emptyQueue("activemq_remaining_count");

        $this->provider->put("activemq_change_queue_1", "message1");
        $this->provider->put("activemq_change_queue_2", "message2");

        $this->assertSame("message1", $this->provider->get("activemq_change_queue_1", 1));
        $this->assertSame("message2", $this->provider->get("activemq_change_queue_2", 1));
    }

    /**
     * @param string $queueName
     */
    private function emptyQueue($queueName)
    {
        while ($this->provider->get($queueName, 1) !== null) {
            // Do nothing
        }
    }
}
