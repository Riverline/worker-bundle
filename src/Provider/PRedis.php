<?php

namespace Riverline\WorkerBundle\Provider;

use Predis\Client;
use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class PRedis
 * @package Riverline\WorkerBundle\Provider
 */
class PRedis extends AbstractBaseProvider
{
    /**
     * @var Client
     */
    protected $predis;

    /**
     * PRedis constructor.
     * @param array $predisConfiguration
     */
    public function __construct($predisConfiguration)
    {
        if (!class_exists('Predis\Client')) {
            throw new \LogicException("Can't find PRedis lib");
        }

        $this->predis = new Client($predisConfiguration);
    }

    /**
     * @param string $queueName
     * @param mixed  $workload
     */
    public function put($queueName, $workload)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->predis->lpush($queueName, serialize($workload));
    }

    /**
     * @param string $queueName
     * @param array  $workloads
     */
    public function multiPut($queueName, array $workloads)
    {
        $pipeline = $this->predis->pipeline();

        foreach ($workloads as $workload) {
            /** @noinspection PhpUndefinedMethodInspection */
            $pipeline->lpush($queueName, serialize($workload));
        }

        $pipeline->execute();
    }

    /**
     * @param string $queueName
     * @param null   $timeout
     * @return mixed|null
     */
    public function get($queueName, $timeout = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $result = $this->predis->brpop($queueName, $timeout);
        if (empty($result)) {
            return null;
        } else {
            return unserialize($result[1]);
        }
    }

    /**
     * @param string $queueName
     * @return mixed
     */
    public function count($queueName)
    {
        /** @noinspection PhpUndefinedMethodInspection */

        return $this->predis->llen($queueName);
    }

    /**
     * @param string $queueName
     * @param array  $queueOptions
     * @return \Riverline\WorkerBundle\Queue\Queue|void
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        // Queue automaticaly created on demand ... nothing to do
        return new Queue($queueName, $this);
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function deleteQueue($queueName)
    {
        /** @noinspection PhpUndefinedMethodInspection */

        return (1 == $this->predis->del($queueName));
    }

    /**
     * @param string $queueName
     * @return bool
     */
    public function queueExists($queueName)
    {
        /** @noinspection PhpUndefinedMethodInspection */

        return (1 == $this->predis->exist($queueName));
    }

    /**
     * @param string $queueName
     * @param array  $queueOptions
     * @return bool
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        // No option on queue ... nothing to do
        return true;
    }
}
