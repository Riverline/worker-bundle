# README

[![Build Status](https://secure.travis-ci.org/rcambien/riverline-worker-bundle.png)](http://travis-ci.org/rcambien/riverline-worker-bundle)

## What is Riverline\WorkerBundle

``Riverline\WorkerBundle`` add abstraction to queue providers and allow to create Workers to consume queue workload.

## Requirements

* PHP 5.3
* Symfony 2.0

## Installation

``Riverline\WorkerBundle`` is compatible with composer and any prs-0 autoloader

## Configuration

```yml
riverline_worker:
    providers:
        predis:
            class: Riverline\WorkerBundle\Provider\PRedis
            arguments:
                - { host: redis.example.com }
        sqs:
            class: Riverline\WorkerBundle\Provider\AwsSQS
            arguments:
                - { key: xxxxxx, secret: xxxxx }
                - sqs.eu-west-1.amazonaws.com
        gearman:
            class: Riverline\WorkerBundle\Provider\Gearman
            arguments:
                - [ gearman1.example.com, gearman2.examplet.com ]
        amqp: ## WIP
            class: Riverline\WorkerBundle\Provider\AMQP
        semaphore:
            class: Riverline\WorkerBundle\Provider\Semaphore
    queues:
        queue1:
            name: ThisIsMyQueue
            provider: predis
        queue2:
            name: https://eu-west-1.queue.amazonaws.com/xxxxxx/xxxx
            provider: sqs
```

## Usage

You can access any configured provider or queue through the Symfony Container

```php
<?php

$provider = $this->get('riverline_worker.provider.predis');
$provider->put('ThisIsMyQueue', 'Hello World');

$queue = $this->get('riverline_worker.queue.queue1');
echo $queue->count()." item(s) in the queue";
```

You can easily create Workers

```php
<?php

// src/Acme/DemoBundle/Command/DemoWorkerCommand.php

namespace Acme\DemoBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Riverline\WorkerBundle\Command\Worker;
use Riverline\WorkerBundle\Command\WorkerControlCodes;

class DemoWorkerCommand extends Worker
{
    protected function configureWorker()
    {
        $this
            // Queue name from the configuration
            ->setQueueName('queue1')

            // Inhered Command methods
            ->setName('demo-worker')
            ->setDescription('Test a worker')
        ;
    }

    protected function executeWorker(InputInterface $input, OutputInterface $output, $workload)
    {
        $output->writeln($workload);

        // Stop worker and dot not process other workloads
        if ($someReasonToStopAndExit)
        {
            return WorkerControlCodes::STOP_EXECUTION;
        }

        // else continue
        return WorkerControlCodes::CAN_CONTINUE;
    }
}

```

Then you can launch your worker like any other command

```sh
$ php app/console demo-worker
Hello World
```

You can pass options.

```sh
$ php app/console --worker-wait-timeout=60 --worker-limit=10 --memory-limit=128 --worker-exit-on-exception
```

This command wait 60 seconds for a workload from the queue, will process a maximum of 10 workloads or exit when usaed memory exceed 128Mb and exit if the ``executeWorker()`` throw an exception.
