<?php

namespace Riverline\WorkerBundle\Provider\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Riverline\WorkerBundle\Provider\Collection\Strategy\StrategyInterface;

/**
 * Class FirstStrategy
 * @package Riverline\WorkerBundle\Provider\Collection
 */
class FirstStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function choose(ArrayCollection $providerCollection)
    {
        return $providerCollection->count() > 0
            ? $providerCollection->first()
            : false;
    }
}
