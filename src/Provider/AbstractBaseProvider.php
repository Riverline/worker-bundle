<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Exception\NotImplementedFeatureException;

/**
 * Class AbstractBaseProvider
 * @package Riverline\WorkerBundle\Provider
 */
abstract class AbstractBaseProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = [])
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function get($queueName, $timeout = null)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueOptions($queueName)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues($queueNamePrefix = null)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueue($queueName, array $queueOptions = [])
    {
        throw new NotImplementedFeatureException('Provider '.__CLASS__.' does not implement '.__METHOD__);
    }
}
