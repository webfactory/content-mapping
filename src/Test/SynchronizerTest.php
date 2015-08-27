<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Test;

use Psr\Log\NullLogger;
use Webfactory\ContentMapping\DestinationAdapter;
use Webfactory\ContentMapping\Mapper;
use Webfactory\ContentMapping\MapResult;
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
    public function synchronizeRewindsSourceQueueAndDestinationQueue()
    {
        $sourceQueue = $this->getMock('\Iterator');
        $sourceQueue->expects($this->once())
            ->method('rewind');
        $this->setUpSourceToReturn($sourceQueue);

        $destinationQueue = $this->getMock('\Iterator');
        $destinationQueue->expects($this->once())
            ->method('rewind');
        $this->setUpDestinationToReturn($destinationQueue);

        $this->synchronizer->synchronize($this->className, false);
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

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerSourceQueuesByCreatingNewObjects()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));
        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->className)
            ->will($this->returnValue($newlyCreatedObject));
        $mapResult = new MapResult($newlyCreatedObject, false);
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue($mapResult));

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeCallsUpdatedAfterCreatingNewObject()
    {
        $idOfNewSourceObject = 1;
        $newSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newSourceObject)));

        $emptySet = new \ArrayIterator();
        $this->setUpDestinationToReturn($emptySet);

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfNewSourceObject));
        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->className)
            ->will($this->returnValue($newlyCreatedObject));
        $mapResult = new MapResult($newlyCreatedObject, false);
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue($mapResult));
        $this->destination->expects($this->once())
            ->method('updated');

        $this->synchronizer->synchronize($this->className, false);
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
        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfNewSourceObject, $this->className)
            ->will($this->returnValue($newlyCreatedObject));
        $mapResult = new MapResult($newlyCreatedObject, false);
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue($mapResult));
        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerDestinationQueuesByDeletingOutdatedObjects()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new SourceObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($outdatedDestinationObject);

        $this->synchronizer->synchronize($this->className, false);
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

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeHandlesSameSourceIdAsDestinationIdInQueueComparisonByUpdatingObject()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $mapResult = new MapResult($olderVersionOfDestinationObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeCallsUpdatedForObjectsThatGotUpdated()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $mapResult = new MapResult($olderVersionOfDestinationObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->destination->expects($this->once())
            ->method('updated')
            ->with($olderVersionOfDestinationObject);

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeCallsCommitAfterUpdated()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $mapResult = new MapResult($olderVersionOfDestinationObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult, true));;
        $this->destination->expects($this->once())
            ->method('updated');

        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeDoesNotCallUpdatedForObjectsThatRemainedTheSame()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $mapResult = new MapResult($olderVersionOfDestinationObject, false);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->destination->expects($this->never())
            ->method('updated');

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeCanBeForcedToUpdate()
    {
        $sameIdForSourceAndDestinationObject = 1;
        $newerVersionOfSourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($newerVersionOfSourceObject)));

        $olderVersionOfDestinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($olderVersionOfDestinationObject)));

        $this->mapper->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($sameIdForSourceAndDestinationObject));
        $this->mapper->expects($this->once())
            ->method('setForce')
            ->with(true);
        $mapResult = new MapResult($olderVersionOfDestinationObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->destination->expects($this->once())
            ->method('updated');

        $this->synchronizer->synchronize($this->className, true);
    }

    /**
     * @test
     */
    public function synchronizeHandlesLowerSourceIdInQueueComparisonByCreatingObject()
    {
        $idOfSourceObject = 1;
        $sourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($sourceObject)));

        $idOfDestinationObject = 2;
        $destinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($destinationObject)));

        $this->mapper->expects($this->any(0))
            ->method('idOf')
            ->will($this->returnValue($idOfSourceObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfDestinationObject));

        $newlyCreatedObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfSourceObject, $this->className)
            ->will($this->returnValue($newlyCreatedObject));
        $mapResult = new MapResult($newlyCreatedObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeHandlesHigherSourceIdInQueueComparisonByCreatingObject()
    {
        $idOfSourceObject = 2;
        $sourceObject = new SourceObjectDummy();
        $this->setUpSourceToReturn(new \ArrayIterator(array($sourceObject)));

        $idOfDestinationObject = 1;
        $destinationObject = new DestinationObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($destinationObject)));

        $this->mapper->expects($this->any(0))
            ->method('idOf')
            ->will($this->returnValue($idOfSourceObject));
        $this->destination->expects($this->any())
            ->method('idOf')
            ->will($this->returnValue($idOfDestinationObject));

        $mapResult = new MapResult($destinationObject, true);
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue($mapResult));;

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($destinationObject);

        $this->synchronizer->synchronize($this->className, false);
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