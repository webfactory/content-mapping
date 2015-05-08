<?php

namespace Webfactory\ContentMapping\Test;

use Psr\Log\NullLogger;
use Webfactory\ContentMapping\DestinationAdapter;
use Webfactory\ContentMapping\Mapper;
use Webfactory\ContentMapping\SourceAdapter;
use Webfactory\ContentMapping\Synchronizer;

/**
 * Tests for the Synchronize.
 */
final class SynchronizerTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * System under test.
     *
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * Can be used as the parameter for $this->synchronizer->synchronize().
     *
     * @var string
     */
    private $className = 'arbitrary class';

    /**
     * @var SourceAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $source;

    /**
     * @var Mapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mapper;

    /**
     * @var DestinationAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $destination;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->source = $this->getMock('\Webfactory\ContentMapping\SourceAdapter');
        $this->mapper = $this->getMock('\Webfactory\ContentMapping\Mapper');
        $this->destination = $this->getMock('\Webfactory\ContentMapping\DestinationAdapter');

        $this->synchronizer = new Synchronizer($this->source, $this->mapper, $this->destination, new NullLogger());
    }

    /**
     * @test
     */
    public function synchronizeHandlesEmptySourceObjectsSetAndEmptyDestinationObjectsSet()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);
        $this->setUpDestinationToReturn($emptySet);

        $this->setExpectedException(null);

        $this->synchronizer->synchronize($this->className);
    }

    /**
     * @test
     */
    public function synchronizeCreatesNewObjects()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->mapper->expects($this->any())
                     ->method('idOf')
                     ->will($this->returnValue($idOfNewSourceObject));
        $this->destination->expects($this->once())
                          ->method('createObject')
                          ->with($idOfNewSourceObject, $this->className);

        $this->synchronizer->synchronize($this->className);
    }

    /**
     * @test
     */
    public function synchronizeCallsCommitAfterCreatingNewObject()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->mapper->expects($this->any())
                     ->method('idOf')
                     ->will($this->returnValue($idOfNewSourceObject));
        $this->destination->expects($this->once())
                          ->method('createObject');
        $this->destination->expects($this->once())
                          ->method('commit');

        $this->synchronizer->synchronize($this->className);
    }

    /**
     * @test
     */
    public function synchronizeDeletesOutdatedObjects()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new SourceObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        $this->destination->expects($this->once())
                          ->method('delete')
                          ->with($outdatedDestinationObject);

        $this->synchronizer->synchronize($this->className);
    }

    /**
     * @test
     */
    public function synchronizeCallsCommitAfterDelete()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new SourceObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        $this->destination->expects($this->once())
                          ->method('delete');

        $this->destination->expects($this->once())
                          ->method('commit');

        $this->synchronizer->synchronize($this->className);
    }

    /**
     * @test
     */
    public function synchronizeCallsUpdatedForObjectsThatGotUpdated()
    {
        $this->fail();
    }

    /**
     * @test
     */
    public function synchronizeCallsCommitAfterUpdated()
    {
        $this->fail();
    }

    /**
     * @test
     */
    public function synchronizeDoesNotCallUpdatedForObjectsThatRemainedTheSame()
    {
        $this->fail();
    }

    /**
     * @test
     */
    public function synchronizeHandlesSourceListWithMoreThanOneEntry()
    {
        $this->fail();
    }

    /**
     * @param \Iterator $sourceObjects
     */
    private function setUpSourceToReturn(\Iterator $sourceObjects)
    {
        $this->source->expects($this->any())
                     ->method('getObjectsOrderedById')
                     ->will($this->returnValue($sourceObjects));
    }

    /**
     * @param \Iterator $destinationObjects
     */
    private function setUpDestinationToReturn(\Iterator $destinationObjects)
    {
        $this->destination->expects($this->any())
                          ->method('getObjectsOrderedById')
                          ->will($this->returnValue($destinationObjects));
    }
}
