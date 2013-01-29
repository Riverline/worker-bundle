<?php

namespace Riverline\WorkerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Riverline\WorkerBundle\Queue\Queue;

/**
 * Worker.
 *
 * @author Romain Cambien <romain@cambien.net>
 */
abstract class Worker extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var int
     */
    private $limit = 0;

    /**
     * @var int
     */
    private $count = 0;

    final protected function configure()
    {
        // Generic Options
        $this
            ->addOption('worker-wait-timeout', null, InputOption::VALUE_REQUIRED, 'Number of second to wait for a new workload', 0)
            ->addOption('worker-limit', null, InputOption::VALUE_REQUIRED, 'Number of workload to process', 0)
            ->addOption('worker-exit-on-exception', null, InputOption::VALUE_NONE, 'Stop the worker on error')
        ;

        $this->configureWorker();

        if (!$this->queueName) {
            throw new \LogicException('The worker queue name cannot be empty.');
        }
    }

    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getQueue();

        $this->limit = intval($input->getOption('worker-limit'));
        while(
               $this->canContinueExecution()
            && null !== ($workload = $queue->get($input->getOption('worker-wait-timeout')))
        ) {
            $this->count++;

            try {
                $this->executeWorker($input, $output, $workload);
            } catch (\Exception $e) {
                if ($input->getOption('worker-exit-on-exception')) {
                    throw $e;
                    break;
                }
            }
        }
    }

    /**
     * Indicates if the worker can process another workload.
     * Reasons :
     *   - limit reached
     *   - memory limit reached
     *   - custom limit reached
     *
     * @return boolean
     */
    protected function canContinueExecution()
    {
        // No limit or limit not reach
        return (0 === $this->limit || $this->count <= $this->limit);
    }

    protected function configureWorker()
    {

    }

    protected function executeWorker(InputInterface $input, OutputInterface $output, $workload)
    {
        throw new \LogicException('You must override the executeWorker() method in the concrete worker class.');
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $this->container = $this->getApplication()->getKernel()->getContainer();
        }

        return $this->container;
    }

    /**
     * @see ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Get the queue
     * @return Queue
     */
    protected function getQueue()
    {
        return $this->getContainer()->get('riverline_worker.queue.'.$this->queueName);
    }

    /**
     * @param string $queueName
     * @return Worker
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }
}