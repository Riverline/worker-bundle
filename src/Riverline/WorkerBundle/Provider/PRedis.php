<?php

namespace Riverline\WorkerBundle\Provider;

class PRedis implements ProviderInterface
{
    protected $predis;

    public function __construct($predisConfiguration)
    {
        if (!class_exists('Predis\Client')) {
            throw new \LogicException("Can't find PRedis lib");
        }

        $this->predis = new \Predis\Client($predisConfiguration);
    }

    public function put($queueName, $workload)
    {
        $this->predis->lpush($queueName, serialize($workload));
    }

    public function get($queueName, $timeout = null)
    {
        $result = $this->predis->brpop($queueName, $timeout);
        if (empty($result)) {
            return null;
        } else {
            return unserialize($result[1]);
        }
    }

    public function count($queueName)
    {
        return $this->predis->llen($queueName);
    }
}