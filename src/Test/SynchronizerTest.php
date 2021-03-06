<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Test;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Webfactory\ContentMapping\ContentMappingException;
use Webfactory\ContentMapping\DestinationAdapter;
use Webfactory\ContentMapping\Mapper;
use Webfactory\ContentMapping\MapResult;
use Webfactory\ContentMapping\SourceAdapter;
use Webfactory\ContentMapping\Synchronizer;
use Webfactory\ContentMapping\Test\Stubs\DestinationObjectDummy;
use Webfactory\ContentMapping\Test\Stubs\DestinationStub;
use Webfactory\ContentMapping\Test\Stubs\MapperStub;
use Webfactory\ContentMapping\Test\Stubs\SourceObjectDummy;
use Webfactory\ContentMapping\Test\Stubs\SourceStub;

/**
 * Tests for the Synchronize.
 */
final class SynchronizerTest extends TestCase
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
        $this->source = $this->createMock(SourceAdapter::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->destination = $this->createMock(TestDestinationAdapterInterfaces::class);

        $this->synchronizer = new Synchronizer($this->source, $this->mapper, $this->destination, new NullLogger());
    }

    /**
     * @test
     */
    public function synchronizeRewindsSourceQueueAndDestinationQueue()
    {
        $sourceQueue = $this->createMock(\Iterator::class);
        $sourceQueue->expects($this->once())
            ->method('rewind');
        $this->setUpSourceToReturn($sourceQueue);

        $destinationQueue = $this->createMock(\Iterator::class);
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

        $this->synchronizer->synchronize($this->className, false);

        $this->assertTrue(true, 'Successful test');
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
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue(MapResult::unchanged()));

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function synchronizeCallsHooksAfterCreatingNewObject()
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
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue(MapResult::unchanged()));
        $this->destination->expects($this->once())
            ->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        // commit() called at the end
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
    public function synchronizeCallsHooksAfterDelete()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);

        $outdatedDestinationObject = new SourceObjectDummy();
        $this->setUpDestinationToReturn(new \ArrayIterator(array($outdatedDestinationObject)));

        // delete() indicated object has to be removed
        $this->destination->expects($this->once())
            ->method('delete');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        // commit() always called at the end
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
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::changed($olderVersionOfDestinationObject)));

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
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::changed($olderVersionOfDestinationObject)));

        $this->destination->expects($this->once())
            ->method('updated')
            ->with($olderVersionOfDestinationObject);

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

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
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::unchanged()));

        $this->destination->expects($this->never())
            ->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

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
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::changed($olderVersionOfDestinationObject)));

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
        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::changed($newlyCreatedObject)));

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

        $this->mapper->expects($this->once())
            ->method('map')
            ->will($this->returnValue(MapResult::changed($destinationObject)));

        $this->destination->expects($this->once())
            ->method('delete')
            ->with($destinationObject);

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function rejects_source_ids_out_of_order()
    {
        $this->expectException(ContentMappingException::class);

        $source = new SourceStub(new \ArrayIterator([new SourceObjectDummy(2), new SourceObjectDummy(1)]));
        $destination = new DestinationStub(new \ArrayIterator([new SourceObjectDummy(1), new SourceObjectDummy(2)]));
        $synchronizer = new Synchronizer($source, new MapperStub(), $destination);

        $synchronizer->synchronize('test');
    }

    /**
     * @test
     */
    public function rejects_destination_ids_out_of_order()
    {
        $this->expectException(ContentMappingException::class);

        $source = new SourceStub(new \ArrayIterator([new SourceObjectDummy(1), new SourceObjectDummy(2)]));
        $destination = new DestinationStub(new \ArrayIterator([new SourceObjectDummy(2), new SourceObjectDummy(1)]));
        $synchronizer = new Synchronizer($source, new MapperStub(), $destination);

        $synchronizer->synchronize('test');
    }

    /**
     * @test
     */
    public function skips_new_unmappable_source_objects()
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
        $this->mapper->expects($this->any())
            ->method('map')
            ->will($this->returnValue(MapResult::unmappable()));
        $this->destination->expects($this->never())
            ->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        // commit() called at the end
        $this->destination->expects($this->once())
            ->method('commit');

        $this->synchronizer->synchronize($this->className, false);
    }

    /**
     * @test
     */
    public function deletes_unmappable_source_objects()
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
            ->method('map')
            ->will($this->returnValue(MapResult::unmappable()));

        $this->destination->expects($this->once())
            ->method('delete');

        // afterObjectProcessed() called for every object
        $this->destination->expects($this->once())
            ->method('afterObjectProcessed');

        $this->destination->expects($this->once())
            ->method('commit');

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
