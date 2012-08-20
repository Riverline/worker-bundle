<?php

namespace Riverline\WorkerBundle\Provider;

class AwsSQS implements ProviderInterface
{
    /**
     * @var \AmazonSQS
     */
    protected $sqs;

    public function __construct($awsConfiguration, $region = null)
    {
        if (!class_exists('AmazonSQS')) {
            throw new \LogicException("Can't find AWS SDK");
        }

        $this->sqs = new \AmazonSQS($awsConfiguration);
        if (null !== $region) {
            $this->sqs->set_region($region);
        }
    }

    public function put($queueName, $workload)
    {
        $this->parseResponse($this->sqs->send_message($queueName, serialize($workload)));
    }

    public function get($queueName, $timeout = null)
    {
        // Simulate timeout
        $tic = time();

        do {
            $workload = $this->parseResponse($this->sqs->receive_message($queueName))
                ->ReceiveMessageResult->Message;
            if ($workload) {
                $this->parseResponse($this->sqs->delete_message($queueName, strval($workload->ReceiptHandle)));
                if (md5($workload->Body) == $workload->MD5OfBody) {
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

    public function count($queueName)
    {
        $response = $this->sqs->get_queue_size($queueName);
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
            throw new \RuntimeException($response->body->Error->Message);
        }
    }
}