<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

class Mockup implements ProviderInterface
{
    static protected $queues;

    /**
     * @var array
     */
    static protected $queueOptions = array();

    public function put($queueName, $workload)
    {
        if (isset(self::$queues[$queueName])) {
            self::$queues[$queueName][] = $workload;
        } else {
            self::$queues[$queueName] = array($workload);
        }
    }

    public function get($queueName, $timeout = null)
    {
        if (null !== $timeout) {
            throw new \LogicException("Array provider doesn't support timeout");
        }

        if (isset(self::$queues[$queueName]) && count(self::$queues[$queueName])) {
            return array_shift(self::$queues[$queueName]);
        } else {
            return null;
        }
    }

    public function count($queueName)
    {
        if (isset(self::$queues[$queueName])) {
            return count(self::$queues[$queueName]);
        } else {
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        foreach($workloads as $workload) {
            $this->put($queueName, $workload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        if (! isset(self::$queues[$queueName])) {
            self::$queues[$queueName] = array();
        }

        return new Queue($queueName, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        if (isset(self::$queues[$queueName])) {
            unset(self::$queues[$queueName]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueOptions($queueName)
    {
        return isset(self::$queueOptions[$queueName])
            ? self::$queueOptions[$queueName]
            : array();
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        return isset(self::$queues[$queueName]);
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues($queueNamePrefix = null)
    {
        return array_keys(self::$queues);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        self::$queueOptions[$queueName] = $queueOptions;
    }
}
