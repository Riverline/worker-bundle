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
     * @var ActiveMQ
     */
    private $provider;

    public function setUp(): void
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

    public function testCount(): void
    {
        $this->emptyQueue("activemq_queue_empty");
        self::assertSame(0, $this->provider->count("activemq_queue_empty"));

        for ($i = 0; $i < 2; $i++) {
            $this->provider->put("activemq_queue_empty", "test" . $i);
        }

        self::assertSame(2, $this->provider->count("activemq_queue_empty"));
    }

    public function testCreateQueueWithSpecialCharacters(): void
    {
        $this->provider->put("bad_queue name", "bad_queue name");
        self::assertSame("bad_queue name", $this->provider->get("bad_queue name", 1));

        $this->provider->put("bad_queue namé", "bad_queue namé");
        self::assertSame("bad_queue namé", $this->provider->get("bad_queue namé", 1));

        $this->provider->put("bad_queue %%%", "bad_queue %%%");
        self::assertSame("bad_queue %%%", $this->provider->get("bad_queue %%%", 1));
    }

    public function testCreateQueue(): void
    {
        $newQueue = $this->provider->createQueue("activemq_create_queue");

        self::assertInstanceOf(Queue::class, $newQueue);
    }

    public function testGetNoFrame(): void
    {
        self::assertNull($this->provider->get("activemq_no_frame", 1));
    }

    public function testGetOnMultipleQueues(): void
    {
        $this->provider->put("activemq_get_1", "message1");
        $this->provider->put("activemq_get_2", "message2");

        self::assertSame("message1", $this->provider->get("activemq_get_1", 1));
        self::assertSame("message2", $this->provider->get("activemq_get_2", 1));
    }

    /**
     *
     */
    public function testMultiPut(): void
    {
        $this->emptyQueue("activemq_multi_put");
        $this->provider->multiPut(
            "activemq_multi_put",
            [
                "message1",
                "message2",
                ["foo" => "bar"],
                new \stdClass(),
            ]
        );

        self::assertSame("message1", $this->provider->get("activemq_multi_put", 1));
        self::assertSame("message2", $this->provider->get("activemq_multi_put", 1));
        self::assertSame(["foo" => "bar"], $this->provider->get("activemq_multi_put", 1));
        self::assertInstanceOf(\stdClass::class, $this->provider->get("activemq_multi_put", 1));
        self::assertNull($this->provider->get("activemq_multi_put", 1));
    }

    public function testPutAndGet(): void
    {
        $this->emptyQueue("activemq_put_get");

        $this->provider->put("activemq_put_get", "coucou");
        self::assertSame("coucou", $this->provider->get("activemq_put_get", 1));
    }

    public function testRemainingCount(): void
    {
        $this->emptyQueue("activemq_remaining_count");

        $this->provider->put("activemq_remaining_count", "coucou");
        $this->provider->put("activemq_remaining_count", "coucou2");
        $this->provider->put("activemq_remaining_count", "coucou3");

        self::assertSame("coucou", $this->provider->get("activemq_remaining_count", 1));
        self::assertSame(2, $this->provider->count("activemq_remaining_count"));

        self::assertSame("coucou2", $this->provider->get("activemq_remaining_count", 1));
        self::assertSame(1, $this->provider->count("activemq_remaining_count"));
    }

    public function testChangeQueue(): void
    {
        $this->emptyQueue("activemq_remaining_count");

        $this->provider->put("activemq_change_queue_1", "message1");
        $this->provider->put("activemq_change_queue_2", "message2");

        self::assertSame("message1", $this->provider->get("activemq_change_queue_1", 1));
        self::assertSame("message2", $this->provider->get("activemq_change_queue_2", 1));
    }

    private function emptyQueue($queueName): void
    {
        while ($this->provider->get($queueName, 1) !== null) {
            // Do nothing
        }
    }
}
