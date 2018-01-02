<?php

namespace Riverline\WorkerBundle\Provider;

abstract class BaseProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function get($queueName, $timeout = null)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueOptions($queueName)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues($queueNamePrefix = null)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        throw new \Exception('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

}
