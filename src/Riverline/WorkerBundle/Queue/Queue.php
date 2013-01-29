<?php

namespace Riverline\WorkerBundle\Queue;

use Riverline\WorkerBundle\Provider\ProviderInterface;

class Queue
{
    /**
     * The queue name
     * @var string
     */
    protected $name;

    /**
     * The queue provider
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * @param string $name The queue name
     * @param \Riverline\WorkerBundle\Provider\ProviderInterface $provider The queue provider
     */
    public function __construct($name, ProviderInterface $provider)
    {
        $this->name     = $name;
        $this->provider = $provider;
    }

    /**
     * Add workloads in the queue
     * @param array $workloads
     */
    public function multiPut(array $workloads)
    {
        $this->provider->multiPut($this->name, $workloads);
    }

    /**
     * Add a workload in the queue
     * @param $workload
     */
    public function put($workload)
    {
        $this->provider->put($this->name, $workload);
    }

    /**
     * Get a workload from the queue
     * @param int|null $timeout
     * @return mixed|null
     */
    public function get ($timeout = null)
    {
        return $this->provider->get($this->name, $timeout);
    }

    /**
     * Return the number of item in the queue
     * @return int
     */
    public function count()
    {
        return $this->provider->count($this->name);
    }
}