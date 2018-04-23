<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Exception\NotImplementedFeatureException;
use Riverline\WorkerBundle\Queue\Queue;
use Stomp\Client;
use Stomp\Network\Connection;
use Stomp\StatefulStomp;
use Stomp\Transport\Message;

/**
 * Class ActiveMQ
 * @package Riverline\WorkerBundle\Provider
 */
class ActiveMQ extends AbstractBaseProvider
{
    /**
     * @var StatefulStomp
     */
    private $stomp;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $statisticsPluginEnabled;

    /**
     * @var bool
     */
    private $persistentMessage;

    /**
     * ActiveMQ constructor.
     *
     * @param string $brokerUri
     * @param string $login
     * @param string $password
     * @param bool   $persistentMessage
     * @param bool   $statisticsPluginEnabled
     * @param bool   $syncEnabled
     *
     * @throws \Stomp\Exception\ConnectionException
     */
    public function __construct(
        $brokerUri,
        $login,
        $password,
        $persistentMessage = false,
        $statisticsPluginEnabled = false,
        $syncEnabled = true
    ) {
        $connection = new Connection($brokerUri);

        $this->client = new Client($connection);
        $this->client->setLogin($login, $password);
        $this->client->setSync($syncEnabled);
        $this->client->setClientId(uniqid());

        $this->stomp = new StatefulStomp($this->client);

        $this->statisticsPluginEnabled = (bool) $statisticsPluginEnabled;
        $this->persistentMessage       = (bool) $persistentMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        if (false === $this->statisticsPluginEnabled) {
            throw new NotImplementedFeatureException("ActiveMQ statistics plugin must be enabled");
        }

        $this->client->getConnection()->setReadTimeout(60);
        $this->unsubscribe();

        $tempQueueName = sprintf("/temp-queue/%s", uniqid());
        $this->client->sendFrame(new Message(
            "",
            [
                "reply-to"    => $tempQueueName,
                "destination" => "ActiveMQ.Statistics.Destination.".$queueName,
            ]
        ));

        $this->subscribe($tempQueueName);

        while ($frame = $this->stomp->read()) {
            if ($frame->getHeaders()["destination"] === $tempQueueName) {
                break;
            }
        }

        if (false === $frame) {
            // No queue existing
            return 0;
        }

        $this->stomp->ack($frame);

        try {
            $statistics = new \SimpleXMLElement($frame->body);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf("Unable to decode statistics frame body : %s", $frame->body));
        }

        $elements = $statistics->xpath("/map/entry[string='size'][1]");
        $element  = array_shift($elements);

        return intval($element->long);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = [])
    {
        return new Queue($queueName, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function get($queueName, $timeout = null)
    {
        $this->client->getConnection()->setReadTimeout($timeout ?: 60, 0);

        $queueName = $this->getQueueName($queueName);
        $this->subscribe($queueName);

        while ($frame = $this->stomp->read()) {
            if ($frame["destination"] === $queueName) {
                $this->stomp->getClient()->sendFrame($this->client->getProtocol()->getAckFrame($frame), null);
                break;
            } else {
                // Message redelivery ?
                $this->stomp->getClient()->sendFrame($this->client->getProtocol()->getNackFrame($frame), null);
            }
        }

        return $frame ? unserialize($frame->body) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        foreach ($workloads as $workload) {
            $this->put($queueName, $workload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        //Unsubscribe if already subsribed
        $this->unsubscribe();

        $this->stomp->send(
            $this->getQueueName($queueName),
            new Message(serialize($workload), ['persistent' => $this->persistentMessage ? 'true' : 'false'])
        );
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    private function getQueueName($queueName)
    {
        return sprintf("/queue/%s", $queueName);
    }

    /**
     * @param string $queueName
     */
    private function subscribe($queueName)
    {
        $subscription = $this->stomp->getSubscriptions()->getLast();

        if (false === $subscription || $subscription->getDestination() !== $queueName) {
            if ($subscription) {
                $this->stomp->unsubscribe($subscription->getSubscriptionId());
            }

            $this->stomp->subscribe(
                $queueName,
                null,
                "client-individual",
                ["activemq.prefetchSize" => 1]
            );
        }
    }

    /**
     *
     */
    private function unsubscribe()
    {
        $subscription = $this->stomp->getSubscriptions()->getLast();

        if ($subscription) {
            $this->stomp->unsubscribe($subscription->getSubscriptionId());
        }
    }
}
