<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

/**
 * Class AwsSQS
 * @package Riverline\WorkerBundle\Provider
 *
 * @deprecated Use SDK v2 or v3
 */
class AwsSQS extends BaseProvider
{
    /**
     * @var \AmazonSQS
     */
    protected $sqs;

    /**
     * @var array
     */
    protected $queueUrls = array();

    /**
     * AwsSQS constructor.
     *
     * @param array  $awsConfiguration
     * @param string $region
     */
    public function __construct($awsConfiguration, $region = null)
    {
        trigger_error("AwsSDK Provider is no longer maintened. Please use Aws Sdk v2/v3", E_USER_DEPRECATED);

        if (!class_exists('AmazonSQS')) {
            throw new \LogicException("Can't find AWS SDK");
        }

        $this->sqs = new \AmazonSQS($awsConfiguration);
        if (null !== $region) {
            $this->sqs->set_region($region);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        $attributes = array();
        foreach($queueOptions as $name => $value) {
            $attributes[] = array(
                'Name'  => $name,
                'Value' => $value,
            );
        }

        // Enable Long Polling by default
        if (! isset($attributes['ReceiveMessageWaitTimeSeconds'])) {
            $attributes[] = array(
                'Name'  => 'ReceiveMessageWaitTimeSeconds',
                'Value' => 20,
            );
        }

        $response = $this->parseResponse($this->sqs->create_queue($queueName, array('Attribute' => $attributes)));

        return new Queue($this->extractQueueNameFromUrl((string)$response->CreateQueueResult->QueueUrl), $this);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        $this->parseResponse($this->sqs->delete_queue($this->getQueueUrl($queueName)));

        return true;
    }

    /**
     * Extract queue name from AWS queue url.
     *
     * @param string $queueUrl
     * @return string Queue name
     */
    private function extractQueueNameFromUrl($queueUrl)
    {
        return substr(strrchr((string)$queueUrl, '/'), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueOptions($queueName)
    {
        $response = $this->parseResponse($this->sqs->get_queue_attributes($this->getQueueUrl($queueName), array('AttributeName' => 'All')));

        $attributes = array();
        foreach($response->GetQueueAttributesResult->Attribute as $attribute) {
            $attributes[(string)$attribute->Name] = (string)$attribute->Value;
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues($queueNamePrefix = null)
    {
        $options = array();
        if (! is_null($queueNamePrefix)) {
            $options['QueueNamePrefix'] = $queueNamePrefix;
        }

        $response = $this->parseResponse($this->sqs->list_queues($options));

        $queues = array();
        foreach($response->ListQueuesResult->QueueUrl as $queueUrl) {
            $queues[] = $this->extractQueueNameFromUrl($queueUrl);
        }

        return $queues;
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        try {
            $queueUrl = $this->parseResponse($this->sqs->get_queue_url($queueName));
        } catch(\RuntimeException $re) {
            if ('AWS.SimpleQueueService.NonExistentQueue' === $re->getMessage()) {
                return false;
            }

            throw $re;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        $queueUrl = $this->getQueueUrl($queueName);

        $batchWorkloads  = array();
        $batchWorkloadId = 1;
        foreach($workloads as $workload) {
            $batchWorkloads[] = array(
                'Id'          => $batchWorkloadId++,
                'MessageBody' => serialize($workload)
            );
        }

        $response = $this->sqs->send_message_batch($queueUrl, $batchWorkloads);

        $this->parseResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        $this->parseResponse($this->sqs->send_message($queueUrl, serialize($workload)));
    }

    /**
     * {@inheritdoc}
     */
    public function get($queueName, $timeout = null)
    {
        // Simulate timeout
        $tic = time();

        /** @todo update for Long Polling attribute */

        do {
            $queueUrl = $this->getQueueUrl($queueName);
            $workload = $this->parseResponse($this->sqs->receive_message($queueUrl))
                ->ReceiveMessageResult->Message;
            if ($workload) {
                $this->parseResponse($this->sqs->delete_message($queueUrl, strval($workload->ReceiptHandle)));
                if (md5((string)$workload->Body) == $workload->MD5OfBody) {
                    return unserialize($workload->Body);
                } else {
                    throw new \RuntimeException('Corrupted response');
                }
            } else {
                // Wait
                sleep(1);
            }
        } while(null !== $timeout && (time() - $tic < $timeout));

        return null;
    }

    /**
     * @param string $queueName
     * @return string AWS queue url
     */
    private function getQueueUrl($queueName)
    {
        if (! isset($this->queueUrls[$queueName])) {
            $this->queueUrls[$queueName] = (string)$this->parseResponse($this->sqs->get_queue_url($queueName))->GetQueueUrlResult->QueueUrl;
        }

        return $this->queueUrls[$queueName];
    }

    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        $response = $this->sqs->get_queue_size($queueUrl);
        if (is_numeric($response)) {
            return $response;
        } else {
            return $this->parseResponse($response);
        }
    }

    /**
     * Parse Amazon response
     * @param \CFResponse $response
     * @return string
     * @throws \RuntimeException
     */
    protected function parseResponse(\CFResponse $response)
    {
        if ($response->isOk()) {
            return $response->body;
        } else {
            throw new \RuntimeException($response->body->Error->Code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        $attributes = array();
        foreach($queueOptions as $name => $value) {
            $attributes[] = array(
                'Name'  => $name,
                'Value' => $value,
            );
        }

        $this->parseResponse($this->sqs->set_queue_attributes($this->getQueueUrl($queueName), $attributes));

        return true;
    }

}
