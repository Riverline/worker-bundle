<?php

namespace Riverline\WorkerBundle\Provider;

class Gearman implements ProviderInterface
{
    /**
     * @var array
     */
    protected $workers = array();

    /**
     * @var \GearmanClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $servers;

    /**
     * @var mixed
     */
    protected $workload;

    public function __construct($servers) {
        if (!class_exists('GearmanClient')) {
            throw new \LogicException("Can't find Gearman lib");
        }

        // Servers
        $this->servers = (array)$servers;
    }

    public function get($queueName, $timeout = null)
    {
        $worker = $this->getWorker($queueName);

        $worker->setTimeout($timeout?:-1);

        @$worker->work();

        if ($worker->returnCode() == GEARMAN_SUCCESS) {
            return $this->workload;
        } else if (in_array($worker->returnCode(), array(GEARMAN_IO_WAIT, GEARMAN_TIMEOUT, GEARMAN_NO_JOBS))) {
            return null;
        } else {
            throw new \RuntimeException($worker->error());
        }
    }

    public function put($queueName, $workload) {
        if (null === $this->client) {
            $this->client = new \GearmanClient();
            foreach($this->servers as $server) {
                $this->client->addServer($server);
            }
        }

        $workload = serialize($workload);

        $this->client->doBackground($queueName, $workload);

        if ($this->client->returnCode() != GEARMAN_SUCCESS) {
            throw new \RuntimeException($this->client->error());
        }
    }

    public function count($queueName) {
        throw new \LogicException("Can't get amount of workload with Gearman provider");
    }

    /**
     * Get GearmanWorker object.
     * @param string $queueName Queue name
     * @return \GearmanWorker
     */
    protected function getWorker($queueName) {
        if (!isset($this->workers[$queueName])) {
            $worker = new \GearmanWorker();

            foreach($this->servers as $server) {
                $worker->addServer($server);
            }

            $worker->addFunction($queueName, array($this, 'work'));

            $this->workers[$queueName] = $worker;
        }

        return $this->workers[$queueName];
    }

    /**
     * Wrapper function to retrieve the workload
     * @param \GearmanJob $job
     */
    protected function work($job)
    {
        $this->workload = unserialize($job->workload());
    }
}
