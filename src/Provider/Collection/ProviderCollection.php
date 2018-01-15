<?php

namespace Riverline\WorkerBundle\Provider\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Riverline\WorkerBundle\Provider\Collection\Strategy\StrategyInterface;
use Riverline\WorkerBundle\Provider\ProviderInterface;

/**
 * Class ProviderCollection
 * @package Riverline\WorkerBundle\Provider
 */
class ProviderCollection
{
    /**
     * @var ArrayCollection|ProviderInterface[]
     */
    private $providers;

    /**
     * LoadBalancer constructor.
     */
    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * @param string            $name
     * @param ProviderInterface $provider
     */
    public function addProvider($name, ProviderInterface $provider)
    {
        $this->providers->set($name, $provider);
    }

    /**
     * @param StrategyInterface|\Closure $strategy
     *
     * @return false|ProviderInterface
     */
    public function find($strategy)
    {
        if (!$strategy instanceof StrategyInterface && !$strategy instanceof \Closure) {
            throw new \RuntimeException(sprintf(
                "You should provide a %s instance or a Closure in method %s",
                StrategyInterface::class,
                __METHOD__
            ));
        }

        if ($strategy instanceof StrategyInterface) {
            $provider = $strategy->choose($this->providers);
        }

        if ($strategy instanceof \Closure) {
            $provider = $strategy->__invoke($this->providers);
        }

        if (!$provider instanceof ProviderInterface) {
            throw new \LogicException(
                sprintf("Strategy %s must return a %s instance", get_class($strategy), ProviderInterface::class)
            );
        }

        return $provider;
    }

    /**
     * @return ArrayCollection|ProviderInterface[]
     */
    public function all()
    {
        return $this->providers;
    }
}
