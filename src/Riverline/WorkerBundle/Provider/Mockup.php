<?php

namespace Riverline\WorkerBundle\Provider;

class Mockup implements ProviderInterface
{
    static protected $queues;

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
}