# README

[![Build Status](https://secure.travis-ci.org/rcambien/riverline-worker-bundle.png)](http://travis-ci.org/rcambien/riverline-worker-bundle)

## What is Riverline\WorkerBundle

``Riverline\WorkerBundle`` add abstraction to queue providers and allow to create Workers to consume queue workload.

## Requirements

* PHP 5.6
* Symfony 2.x

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
        sqs: #v1
            class: Riverline\WorkerBundle\Provider\AwsSQS
            arguments:
                - { key: xxxxxx, secret: xxxxx }
                - sqs.eu-west-1.amazonaws.com
        sqs: #v3
            class: Riverline\WorkerBundle\Provider\AwsSQSv3
            arguments:
                -
                    version: "latest"
                    region: "us-west-2"
                    credentials:
                        key: "xxxxxx"
                        secret: "xxxxxx"
        gearman:
            class: Riverline\WorkerBundle\Provider\Gearman
            arguments:
                - [ gearman1.example.com, gearman2.examplet.com ]
        amqp: ## WIP
            class: Riverline\WorkerBundle\Provider\AMQP
        semaphore:
            class: Riverline\WorkerBundle\Provider\Semaphore
        activemq:
            class: Riverline\WorkerBundle\Provider\ActiveMQ
            arguments:
                - tcp://localhost:61613
                - login
                - passcode
                - false        # Boolean indicates if message is persistent
                - false        # Boolean indicates if broker statistics plugin is enabled http://activemq.apache.org/statisticsplugin.html

    queues:
        queue1:
            name: ThisIsMyQueue
            provider: predis
        queue2:
            name: https://eu-west-1.queue.amazonaws.com/xxxxxx/xxxx
            provider: sqs
```

## Usage

### Provider / Queue instances

You can access any configured provider or queue through the Symfony Container

```php
<?php

$provider = $container->get('riverline_worker.provider.predis');
$provider->put('ThisIsMyQueue', 'Hello World');

$queue = $container->get('riverline_worker.queue.queue1');
echo $queue->count()." item(s) in the queue";
```

All configured providers are available in a collection

```php
<?php

$collection = $container->get('riverline.worker_bundle.providers.collection');
$collection->all(); # Return a providers collection

$collection->find(function(\Doctrine\Common\Collections\ArrayCollection $providers) {
    if ($condition) {
        return $providers->get("activemq");
    }
    
    return $providers->get("predis"); 
}); # Return provider depends of your own logic

```

Instead of using closure, you can implements your own strategy class

```php
<?php

class FirstStrategy implements \Riverline\WorkerBundle\Provider\Collection\Strategy\StrategyInterface 
{    
    public function choose(\Doctrine\Common\Collections\ArrayCollection $providerCollection)
    {
        return $providerCollection->first();
    }
}

$collection = $container->get('riverline.worker_bundle.providers.collection');
$collection->find(new FirstStrategy()); # Return first configured provider
```

### Workers

You can easily create Workers

```php
<?php

// src/Acme/DemoBundle/Command/DemoWorkerCommand.php

namespace Acme\DemoBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
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
