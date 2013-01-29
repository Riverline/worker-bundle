<?php

namespace Riverline\WorkerBundle\Provider;

class Semaphore extends BaseProvider
{
    protected $semaphore;

    public function put($queueName, $workload)
    {
        msg_send($this->getQueue($queueName), 1, $workload);
    }

    public function get($queueName, $timeout = null)
    {
        if (null !== $timeout) {
            throw new \LogicException("Semaphore provider doesn't support timeout");
        }

        if (msg_receive($this->getQueue($queueName), 1, $type, 1024, $workload, true, MSG_IPC_NOWAIT)) {
            return $workload;
        } else {
            return null;
        }
    }

    public function count($queueName)
    {
        $stats = msg_stat_queue($this->getQueue($queueName));
        return $stats['msg_qnum'];
    }

    protected function getQueue($queueName)
    {
        if (!isset($this->semaphore[$queueName])) {
            $id = crc32($queueName);
            $this->semaphore[$queueName] = msg_get_queue($id);
        }

        return $this->semaphore[$queueName];
    }
}