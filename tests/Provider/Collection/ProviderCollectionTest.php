<?php

namespace Riverline\WorkerBundle\Provider\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Riverline\WorkerBundle\Provider\ProviderInterface;

/**
 * Class ProviderCollectionTest
 * @package Riverline\WorkerBundle\Provider\Collection
 */
class ProviderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProviderCollection
     */
    private $collection;

    /**
     *
     */
    public function setUp()
    {
        $this->collection = new ProviderCollection();
    }

    /**
     *
     */
    public function testShouldFindProviderWithClosure()
    {
        $provider1 = $this->getMock(ProviderInterface::class);
        $provider2 = $this->getMock(ProviderInterface::class);

        $this->collection->addProvider("provider1", $provider1);
        $this->collection->addProvider("provider2", $provider2);

        $provider = $this->collection->find(function (ArrayCollection $collection) {
            return $collection->get("provider2");
        });
        $this->assertSame($provider2, $provider);
    }

    /**
     *
     */
    public function testShouldFindProviderWithStrategyInstance()
    {
        $provider1 = $this->getMock(ProviderInterface::class);
        $provider2 = $this->getMock(ProviderInterface::class);

        $this->collection->addProvider("provider1", $provider1);
        $this->collection->addProvider("provider2", $provider2);

        $provider = $this->collection->find(new FirstStrategy());
        $this->assertSame($provider1, $provider);
    }

    /**
     *
     */
    public function testShouldReturnAllProviders()
    {
        $provider1 = $this->getMock(ProviderInterface::class);
        $provider2 = $this->getMock(ProviderInterface::class);

        $this->collection->addProvider("provider1", $provider1);
        $this->collection->addProvider("provider2", $provider2);

        $this->assertEquals(
            new ArrayCollection([
                "provider1" => $provider1,
                "provider2" => $provider2,
            ]),
            $this->collection->all()
        );
    }

    /**
     *
     */
    public function testShouldThrowExceptionIfNoProviderFound()
    {
        $this->setExpectedException(\LogicException::class);
        $this->collection->find(function () {
            return null;
        });
    }

    /**
     *
     */
    public function testShouldThrowExceptionIfNotAStrategyInstanceOrClosureArgumentWhenFind()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->collection->find(new \stdClass());
    }
}
