<?php

namespace Riverline\WorkerBundle\Provider;

interface ProviderInterface
{
    /**
     * Put a workload in the queue
     * @abstract
     * @param string $queueName The queue name
     * @param mixed  $workload  The workload
     */
    public function put($queueName, $workload);

    /**
     * Get a workload from the queue
     * @abstract
     * @param string   $queueName The queue name
     * @param int|null $timeout   The wait timeout for a workload
     * @return mixed|null
     */
    public function get($queueName, $timeout = null);

    /**
     * Get the amount of workload in the queue
     * @abstract
     * @param string $queueName The queue name
     * @return int
     */
    public function count($queueName);
}