<?php

namespace Riverline\WorkerBundle\Provider;

use Riverline\WorkerBundle\Queue\Queue;

/**
 * Use for test purpose only.
 *
 * @package Riverline\WorkerBundle\Provider
 */
class File extends AbstractBaseProvider
{

    /**
     * @var string
     */
    private $directory;

    /**
     * @var array
     */
    private $memoryQueues = array();

    /**
     * @param string $directory
     */
    public function __construct($directory = null)
    {
        $this->directory = empty($directory) ? sys_get_temp_dir().'/'.md5(__FILE__) : $directory;

        if (! file_exists($this->directory)) {
            $old = umask(0);
            mkdir($this->directory, 0777);
            umask($old);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count($queueName)
    {
        $this->reloadQueue($queueName);

        return isset($this->memoryQueues[$queueName]) ? count($this->memoryQueues[$queueName]) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName, array $queueOptions = array())
    {
        $this->memoryQueues[$queueName] = array();

        $this->flushQueue($queueName);

        return new Queue($queueName, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue($queueName)
    {
        $filepath = $this->getFilepath($queueName);

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        if (isset($this->memoryQueues[$queueName])) {
            unset($this->memoryQueues[$queueName]);
        }

        return true;
    }

    /**
     * @param string $queueName
     */
    private function flushQueue($queueName)
    {
        $filepath = $this->getFilepath($queueName);

        $fileCreated = false;
        if (! file_exists($filepath)) {
            $fileCreated = true;
        }

        file_put_contents($filepath, serialize($this->memoryQueues[$queueName]));

        if ($fileCreated) {
            chmod($filepath, 0777);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($queueName, $timeout = null)
    {
        $this->reloadQueue($queueName);

        $item = array_shift($this->memoryQueues[$queueName]);

        $this->flushQueue($queueName);

        return $item;
    }

    /**
     * @param string $queueName
     * @return string
     */
    private function getFilepath($queueName)
    {
        return $this->directory.'/'.$queueName.'.queue';
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues($queueNamePrefix = null)
    {
        $directory = dir($this->directory);

        $queues = array();
        while (false !== ($entry = $directory->read())) {
            if (in_array($entry, array('.', '..'))) {
                continue;
            }

            $queues[] = substr($entry, 0, strlen($entry) - strlen('.queue'));
        }

        return $queues;
    }

    /**
     * @param string $queueName
     */
    private function reloadQueue($queueName)
    {
        $filepath = $this->getFilepath($queueName);

        if (! file_exists($filepath)) {
            if (isset($this->memoryQueues[$queueName])) {
                unset($this->memoryQueues[$queueName]);
            }

            return;
        }

        $serialized = file_get_contents($filepath);

        $this->memoryQueues[$queueName] = unserialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function multiPut($queueName, array $workloads)
    {
        foreach($workloads as $workload) {
            $this->put($queueName, $workload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put($queueName, $workload)
    {
        $this->reloadQueue($queueName);

        $this->memoryQueues[$queueName][] = $workload;

        $this->flushQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function queueExists($queueName)
    {
        $this->reloadQueue($queueName);

        return isset($this->memoryQueues[$queueName]);
    }
}
