<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;
use Aws\Sqs\SqsClient;
use Aws\Sqs\Enum\QueueAttribute;
use Aws\Sqs\Exception\SqsException;

/**
 * Class AwsSQSv2
 */
class AwsSQSv2 extends AbstractBaseProvider
{
    /**
     * @var \Aws\Sqs\SqsClient;
     */
    protected $sqs;

    /**
     * @var int
     */
    protected $messagesPerRequest = 1;

    /**
     * @var array
     */
    protected $messageCache = [];

    /**
     * @var array
     */
    protected $queueUrls = array();

    /**
     * AwsSQSv2 constructor.
     * @param array $awsConfiguration
     * @param int   $messagesPerRequest
     */
    public function __construct($awsConfiguration, $messagesPerRequest = 1)
    {
        if (!class_exists('\Aws\Sqs\SqsClient')) {
            throw new \LogicException("Can't find AWS SDK >= 2.0.0");
        }

        if ($messagesPerRequest < 1 || $messagesPerRequest > 10) {
            throw new \InvalidArgumentException('Messages per request must be between 1 and 10');
        }

        $this->messagesPerRequest = (int) $messagesPerRequest;

        $this->sqs = SqsClient::factory($awsConfiguration);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        foreach ($this->messageCache as $queueName => $messages) {
            if (count($messages) > 0) {
                // Re-inject messages
                try {
                    $this->multiPut($queueName, $messages);
                } catch (\Exception $e) {
                    error_log('Error while reinjecting cached messages');
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = [])
    {
        // Enable Long Polling by default
        if (! isset($queueOptions[QueueAttribute::RECEIVE_MESSAGE_WAIT_TIME_SECONDS])) {
            $queueOptions[QueueAttribute::RECEIVE_MESSAGE_WAIT_TIME_SECONDS] = 20;
        }

        // Extract queue tags from options
        $tags = [];
        if (array_key_exists('tags', $queueOptions)) {
            $tags = (array) $queueOptions['tags'];
            unset($queueOptions['tags']);
        }

        $response = $this->sqs->createQueue([
            'QueueName'  => $queueName,
            'Attributes' => $queueOptions,
            'tags'       => $tags,
        ]);

        return new Queue($this->extractQueueNameFromUrl($response['QueueUrl']), $this);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function deleteQueue($queueName)
    {
        $this->sqs->deleteQueue([
            'QueueUrl' => $this->getQueueUrl($queueName),
        ]);
        unset($this->queueUrls[$queueName]);

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function getQueueOptions($queueName)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl'       => $this->getQueueUrl($queueName),
            'AttributeNames' => array('All'),
        ]);

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
        foreach ($response['QueueUrls'] as $queueUrl) {
            $queues[] = $this->extractQueueNameFromUrl($queueUrl);
        }

        return $queues;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function queueExists($queueName)
    {
        return (null !== $this->getQueueUrl($queueName));
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function multiPut($queueName, array $workloads)
    {
        $queueUrl = $this->getQueueUrl($queueName);

        $batchWorkloads  = array();
        $batchWorkloadId = 1;
        foreach ($workloads as $workload) {
            $workload = base64_encode(gzcompress(serialize($workload), 9));
            $batchWorkloads[] = [
                'Id'          => $batchWorkloadId++,
                'MessageBody' => $workload,
            ];
        }

        $this->sqs->sendMessageBatch([
            'QueueUrl' => $queueUrl,
            'Entries'  => $batchWorkloads,
        ]);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function put($queueName, $workload)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        $workload = base64_encode(gzcompress(serialize($workload), 9));
        $this->sqs->sendMessage([
            'QueueUrl'    => $queueUrl,
            'MessageBody' => $workload,
        ]);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function get($queueName, $timeout = null)
    {
        if (!key_exists($queueName, $this->messageCache)
            || 0 === count($this->messageCache[$queueName])
        ) {
            $this->messageCache[$queueName] = [];

            $queueUrl = $this->getQueueUrl($queueName);
            $options = [
                'QueueUrl'            => $queueUrl,
                'MaxNumberOfMessages' => $this->messagesPerRequest,
            ];
            if ($timeout > 0) {
                $options['WaitTimeSeconds'] = $timeout;
            }

            $response = $this->sqs->receiveMessage($options);

            if ($response->hasKey('Messages')
                && count($response['Messages']) > 0
            ) {
                $deleteEntries = [];
                foreach ($response['Messages'] as $message) {
                    if (md5($message['Body']) === $message['MD5OfBody']) {
                        $this->messageCache[$queueName][] = unserialize(gzuncompress(base64_decode($message['Body'])));
                        $deleteEntries[] = [
                            'Id' => $message['MessageId'],
                            'ReceiptHandle' => $message['ReceiptHandle'],
                        ];
                    }
                }

                $this->sqs->deleteMessageBatch([
                    'QueueUrl' => $queueUrl,
                    'Entries'  => $deleteEntries,
                ]);
            }
        }

        if (0 === count($this->messageCache[$queueName])) {
            return null;
        }

        return array_shift($this->messageCache[$queueName]);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function count($queueName)
    {
        $attributes = $this->getQueueOptions($queueName);

        return intval($attributes['ApproximateNumberOfMessages']);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function updateQueue($queueName, array $queueOptions = array())
    {
        $this->sqs->setQueueAttributes([
            'QueueUrl'   => $this->getQueueUrl($queueName),
            'Attributes' => $queueOptions,
        ]);

        return true;
    }

    /**
     * Extract queue name from AWS queue url.
     *
     * @param string $queueUrl
     *
     * @return string Queue name
     */
    private function extractQueueNameFromUrl($queueUrl)
    {
        return substr(strrchr($queueUrl, '/'), 1);
    }

    /**
     * @param string $queueName
     *
     * @return string AWS queue url
     *
     * @throws \Aws\Sqs\Exception\SqsException|\Exception
     */
    private function getQueueUrl($queueName)
    {
        if (! isset($this->queueUrls[$queueName])) {
            try {
                $response = $this->sqs->getQueueUrl([
                    'QueueName' => $queueName,
                ]);
                $this->queueUrls[$queueName] = $response['QueueUrl'];
            } catch (SqsException $e) {
                if ('AWS.SimpleQueueService.NonExistentQueue' === $e->getAwsErrorCode()) {
                    // Non existing queue
                    return null;
                }

                // Broadcast
                throw $e;
            }
        }

        return $this->queueUrls[$queueName];
    }
}
