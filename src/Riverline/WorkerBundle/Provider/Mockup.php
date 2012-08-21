<?php

namespace Riverline\WorkerBundle\Provider;

class Mockup implements ProviderInterface
{
    protected $queues;

    public function put($queueName, $workload)
    {
        if (isset($this->queues[$queueName])) {
            array_push($this->queues[$queueName], $workload);
        } else {
            $this->queues[$queueName] = array($workload);
        }
    }

    public function get($queueName, $timeout = null)
    {
        if (null !== $timeout) {
            throw new \LogicException("Array provider doesn't support timeout");
        }

        if (isset($this->queues[$queueName]) && count($this->queues[$queueName])) {
            array_shift($this->queues[$queueName]);
        } else {
            return null;
        }
    }

    public function count($queueName)
    {
        if (isset($this->queues[$queueName])) {
            return count($this->queues[$queueName]);
        } else {
            return 0;
        }
    }
}