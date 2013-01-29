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
     * Put multpile workload in the queue
     * @abstract
     * @param string $queueName The queue name
     * @param array  $workloads Array of workloads
     */
    public function multiPut($queueName, array $workloads);

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

    /**
     * Create a new queue.
     * @abstract
     * @param string $queueName    The queue name
     * @param array  $queueOptions The queue options
     * @return \Riverline\WorkerBundle\Queue\Queue
     */
    public function createQueue($queueName, array $queueOptions = array());

    /**
     * Delete a queue.
     * @abstract
     * @param string $queueName The queue name
     * @return boolean
     */
    public function deleteQueue($queueName);

    /**
     * Return queue options.
     *
     * @param string $queueName
     * @return array
     */
    public function getQueueOptions($queueName);

    /**
     * Indicates if a queue exists.
     * @abstract
     * @param string $queueName The queue name
     * @return boolean
     */
    public function queueExists($queueName);

    /**
     * List all the queues.
     * @abstract
     * @param string $queueNamePrefix
     * @return array
     */
    public function listQueues($queueNamePrefix = null);

    /**
     * Update a queue.
     * @abstract
     * @param string $queueName    The queue name
     * @param array  $queueOptions The queue options
     * @return boolean
     */
    public function updateQueue($queueName, array $queueOptions = array());

}