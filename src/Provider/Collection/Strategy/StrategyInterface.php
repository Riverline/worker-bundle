<?php

namespace Riverline\WorkerBundle\Provider\Collection\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Riverline\WorkerBundle\Provider\ProviderInterface;

/**
 * Interface Strategy
 * @package Riverline\WorkerBundle\Provider\Collection\Strategy
 */
interface StrategyInterface
{
    /**
     * @param ArrayCollection|ProviderInterface[] $providerCollection
     *
     * @return ProviderInterface|false
     */
    public function choose(ArrayCollection $providerCollection);
}
