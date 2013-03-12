<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

use Aws\Sqs\SqsClient;
use Aws\Sqs\Enum\QueueAttribute;
use Aws\Sqs\Exception\SqsException;

class AwsSQSv2 extends BaseProvider
{
    /**
     * @var \Aws\Sqs\SqsClient;
     */
    protected $sqs;

    /**
     * @var array
     */
    protected $queueUrls = array();

    public function __construct($awsConfiguration)
    {
        if (!class_exists('\Aws\Sqs\SqsClient')) {
            throw new \LogicException("Can't find AWS SDK >= 2.0.0");
        }

        $this->sqs = SqsClient::factory($awsConfiguration);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        // Enable Long Polling by default
        if (! isset($queueOptions[QueueAttribute::RECEIVE_MESSAGE_WAIT_TIME_SECONDS])) {
            $queueOptions[QueueAttribute::RECEIVE_MESSAGE_WAIT_TIME_SECONDS] = 20;
        }

        $response = $this->sqs->createQueue(array(
            'QueueName'  => $queueName,
            'Attributes' => $queueOptions
        ));

        return new Queue($this->extractQueueNameFromUrl($response['QueueUrl']), $this);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        $this->sqs->deleteQueue(array(
            'QueueUrl' => $this->getQueueUrl($queueName)
        ));

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
        return substr(strrchr($queueUrl, '/'), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueOptions($queueName)
    {
        $response = $this->sqs->getQueueAttributes(array(
            'QueueUrl'       => $this->getQueueUrl($queueName),
            'AttributeNames' => array('All')
        ));

        return $response['Attributes'];
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

        $response = $this->sqs->listQueues($options);

        $queues = array();
        foreach($response['QueueUrls'] as $queueUrl) {
            $queues[] = $this->extractQueueNameFromUrl($queueUrl);
        }

        return $queues;
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        return (null !== $this->getQueueUrl($queueName));
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
            $workload = base64_encode(gzcompress(serialize($workload), 9));
            $batchWorkloads[] = array(
                'Id'          => $batchWorkloadId++,
                'MessageBody' => $workload
            );
        }

        $this->sqs->sendMessageBatch(array(
            'QueueUrl' => $queueUrl,
            'Entries'  => $batchWorkloads
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        $workload = base64_encode(gzcompress(serialize($workload), 9));
        $this->sqs->sendMessage(array(
            'QueueUrl'    => $queueUrl,
            'MessageBody' => $workload
        ));
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
            $response = $this->sqs->receiveMessage(array(
                'QueueUrl'            => $queueUrl,
                'MaxNumberOfMessages' => 1,
            ));

            if (count($response['Messages']) > 0) {
                $workload = $response['Messages'][0];

                $this->sqs->deleteMessage(array(
                    'QueueUrl'      => $queueUrl,
                    'ReceiptHandle' => $workload['ReceiptHandle']
                ));
                if (md5($workload['Body']) == $workload['MD5OfBody']) {
                    return unserialize(gzuncompress(base64_decode($workload['Body'])));
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
     * @throws \Aws\Sqs\Exception\SqsException|\Exception
     * @return string AWS queue url
     */
    private function getQueueUrl($queueName)
    {
        if (! isset($this->queueUrls[$queueName])) {
            try {
                $response = $this->sqs->getQueueUrl(array(
                    'QueueName' =>$queueName
                ));
                $this->queueUrls[$queueName] = $response['QueueUrl'];
            } catch(SqsException $e) {
                if ('AWS.SimpleQueueService.NonExistentQueue' === $e->getExceptionCode()) {
                    // Non existing queue
                    return null;
                } else {
                    // Broadcast
                    throw $e;
                }
            }
        }

        return $this->queueUrls[$queueName];
    }

    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        $attributes = $this->getQueueOptions($queueName);
        return intval($attributes['ApproximateNumberOfMessages']);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        $this->sqs->setQueueAttributes(array(
            'QueueUrl'   => $this->getQueueUrl($queueName),
            'Attributes' => $queueOptions
        ));

        return true;
    }

}