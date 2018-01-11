<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Exception\NotImplementedFeatureException;

/**
 * Interface ProviderInterface
 * @package Riverline\WorkerBundle\Provider
 */
interface ProviderInterface
{
    /**
     * Get the amount of workload in the queue
     * @abstract
     *
     * @param string $queueName The queue name
     *
     * @return int
     * @throws NotImplementedFeatureException
     */
    public function count($queueName);

    /**
     * Create a new queue.
     * @abstract
     *
     * @param string $queueName    The queue name
     * @param array  $queueOptions The queue options
     *
     * @return \Riverline\WorkerBundle\Queue\Queue
     * @throws NotImplementedFeatureException
     */
    public function createQueue($queueName, array $queueOptions = []);

    /**
     * Delete a queue.
     * @abstract
     *
     * @param string $queueName The queue name
     *
     * @return boolean
     * @throws NotImplementedFeatureException
     */
    public function deleteQueue($queueName);

    /**
     * Get a workload from the queue
     * @abstract
     *
     * @param string   $queueName The queue name
     * @param int|null $timeout   The wait timeout for a workload
     *
     * @return mixed|null
     */
    public function get($queueName, $timeout = null);

    /**
     * Return queue options.
     *
     * @param string $queueName
     *
     * @return array
     * @throws NotImplementedFeatureException
     */
    public function getQueueOptions($queueName);

    /**
     * List all the queues.
     * @abstract
     *
     * @param string $queueNamePrefix
     *
     * @return array
     * @throws NotImplementedFeatureException
     */
    public function listQueues($queueNamePrefix = null);

    /**
     * Put multpile workload in the queue
     * @abstract
     *
     * @param string $queueName The queue name
     * @param array  $workloads Array of workloads
     */
    public function multiPut($queueName, array $workloads);

    /**
     * Put a workload in the queue
     * @abstract
     *
     * @param string $queueName The queue name
     * @param mixed  $workload  The workload
     */
    public function put($queueName, $workload);

    /**
     * Indicates if a queue exists.
     * @abstract
     *
     * @param string $queueName The queue name
     *
     * @return boolean
     * @throws NotImplementedFeatureException
     */
    public function queueExists($queueName);

    /**
     * Update a queue.
     * @abstract
     *
     * @param string $queueName    The queue name
     * @param array  $queueOptions The queue options
     *
     * @return boolean
     * @throws NotImplementedFeatureException
     */
    public function updateQueue($queueName, array $queueOptions = []);
}
