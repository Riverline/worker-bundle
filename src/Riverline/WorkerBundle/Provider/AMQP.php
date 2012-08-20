<?php

namespace Riverline\WorkerBundle\Provider;

class AMQP implements ProviderInterface
{
    protected $channel;

    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    protected $queue;

    public function __construct($AMQPConfiguration, $exchangeName = '')
    {
        if (!class_exists('AMQPConnection')) {
            throw new \LogicException("Can't find AMQP lib");
        }

        $ampq = new \AMQPConnection($AMQPConfiguration);
        if ($ampq->connect()) {
            $channel  = new \AMQPChannel($ampq);
            $exchange = new \AMQPExchange($channel);
            $exchange->setName($exchangeName);
            $exchange->declare();

            $this->channel = $channel;
            $this->exchange = $exchange;
        } else {
            throw new \RuntimeException("Can't connect to AMQP server");
        }
    }

    public function put($queueName, $workload)
    {
        $this->exchange->publish(serialize($workload), $queueName);
    }

    public function get($queueName, $timeout = null)
    {
        if(null === $this->queue) {
            $queue = new \AMQPQueue($this->channel);
            $queue->setName($queueName);
            $queue->declare();
        }

        return unserialize($queue->consume());
    }

    public function count($queueName)
    {
    }

}