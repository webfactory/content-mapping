<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Test;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

final class SynchronizerTest extends TestCase
{
    /**
     * Can be used as the parameter for $this->synchronizer->synchronize().
     *
     * @var string
     */
    private $className = 'arbitrary class';

    /**
     * @var SourceAdapter&MockObject
     */
    private $source;

    /**
     * @var Mapper&MockObject
     */
    private $mapper;

    /**
     * @var DestinationAdapter&MockObject
     */
    private $destination;

    /**
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->source = $this->createMock(SourceAdapter::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->mapper->method('idOf')->willReturnCallback(function (SourceObjectDummy $source) {
            return $source->getId();
        });

        $this->destination = $this->createMock(TestDestinationAdapterInterfaces::class);
        $this->destination->method('idOf')->willReturnCallback(function (DestinationObjectDummy $source) {
            return $source->getId();
        });
    }

    /**
     * @test
     */
    public function synchronizeRewindsIterators()
    {
        $sourceQueue = $this->createMock(\Iterator::class);
        $sourceQueue->expects(self::once())->method('rewind');
        $this->setUpSourceToReturn($sourceQueue);

        $destinationQueue = $this->createMock(\Iterator::class);
        $destinationQueue->expects(self::once())->method('rewind');
        $this->setUpDestinationToReturn($destinationQueue);

        $this->runSynchronize();
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function synchronizeHandlesEmptySourceObjectsSetAndEmptyDestinationObjectsSet()
    {
        $emptySet = new \ArrayIterator();
        $this->setUpSourceToReturn($emptySet);
        $this->setUpDestinationToReturn($emptySet);

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerSourceQueuesByCreatingNewObjects()
    {
        $idOfSourceObject = 1;
        $sourceObject = new SourceObjectDummy($idOfSourceObject);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $this->setUpDestinationToReturn(new \ArrayIterator());

        $newObject = new \stdClass();

        $this->destination->expects(self::once())
            ->method('createObject')
            ->with($idOfSourceObject, $this->className)
            ->willReturn($newObject);

        $this->mapper->expects(self::once())
            ->method('map')
            ->with($sourceObject, $newObject)
            ->willReturnCallback(function ($source, $dest) {
                return MapResult::changed($dest);
            });

        $this->destination->expects(self::once())->method('updated')->with($newObject);
        $this->destination->expects(self::once())->method('afterObjectProcessed');
        $this->destination->expects(self::once())->method('commit');

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeHandlesLowerSourceIdInQueueComparisonByCreatingObject()
    {
        $idOfSourceObject = 1;
        $sourceObject = new SourceObjectDummy($idOfSourceObject);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $destinationObject = new DestinationObjectDummy(2);
        $this->setUpDestinationToReturn(new \ArrayIterator([$destinationObject]));

        $newObject = new \stdClass();

        $this->destination->expects(self::once())
            ->method('createObject')
            ->with($idOfSourceObject, $this->className)
            ->willReturn($newObject);

        $this->mapper->expects(self::once())
            ->method('map')
            ->with($sourceObject, $newObject)
            ->willReturnCallback(function ($source, $dest) {
                return MapResult::changed($dest);
            });

        $this->destination->expects(self::once())->method('updated')->with($newObject);

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeHandlesLongerDestinationQueuesByDeletingOutdatedObjects()
    {
        $this->setUpSourceToReturn(new \ArrayIterator());

        $outdatedDestinationObject = new DestinationObjectDummy(1);
        $this->setUpDestinationToReturn(new \ArrayIterator([$outdatedDestinationObject]));

        $this->destination->expects(self::once())
            ->method('delete')
            ->with($outdatedDestinationObject);

        $this->destination->expects(self::once())->method('afterObjectProcessed');
        $this->destination->expects(self::once())->method('commit');

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeHandlesHigherSourceIdInQueueComparisonByDeletingObject()
    {
        $sourceObject = new SourceObjectDummy(2);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $destinationObject1 = new DestinationObjectDummy(1);
        $destinationObject2 = new DestinationObjectDummy(2);
        $this->setUpDestinationToReturn(new \ArrayIterator([$destinationObject1, $destinationObject2]));

        $this->destination->expects(self::once())
            ->method('delete')
            ->with($destinationObject1);

        $this->mapper->method('map')->with($sourceObject, $destinationObject2)->willReturn(MapResult::unchanged());

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeHandlesSameSourceIdAsDestinationIdInQueueComparisonByUpdatingObject()
    {
        $id = 1;
        $sourceObject = new SourceObjectDummy($id);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $destinationObject = new DestinationObjectDummy($id);
        $this->setUpDestinationToReturn(new \ArrayIterator([$destinationObject]));

        $this->mapper->expects(self::once())
            ->method('map')
            ->with($sourceObject, $destinationObject)
            ->willReturn(MapResult::changed($destinationObject));

        $this->destination->expects(self::once())->method('updated')->with($destinationObject);
        $this->destination->expects(self::once())->method('afterObjectProcessed');
        $this->destination->expects(self::once())->method('commit');

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeDoesNotCallUpdatedForObjectsThatRemainedTheSame()
    {
        $id = 1;
        $sourceObject = new SourceObjectDummy($id);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $destinationObject = new DestinationObjectDummy($id);
        $this->setUpDestinationToReturn(new \ArrayIterator([$destinationObject]));

        $this->mapper->expects(self::once())
            ->method('map')
            ->with($sourceObject, $destinationObject)
            ->willReturn(MapResult::unchanged());

        $this->destination->expects(self::never())->method('updated');

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function synchronizeCanBeForcedToUpdate()
    {
        $this->setUpSourceToReturn(new \ArrayIterator([]));
        $this->setUpDestinationToReturn(new \ArrayIterator([]));

        $this->mapper->expects(self::once())
            ->method('setForce')
            ->with(true);

        $this->runSynchronize(true);
    }

    /**
     * @test
     */
    public function rejects_source_ids_out_of_order()
    {
        $this->expectException(ContentMappingException::class);

        $source = new SourceStub(new \ArrayIterator([new SourceObjectDummy(2), new SourceObjectDummy(1)]));
        $destination = new DestinationStub();
        $synchronizer = new Synchronizer($source, new MapperStub(), $destination);

        $synchronizer->synchronize('test');
    }

    /**
     * @test
     */
    public function rejects_destination_ids_out_of_order()
    {
        $this->expectException(ContentMappingException::class);

        $source = new SourceStub();
        $destination = new DestinationStub(new \ArrayIterator([new DestinationObjectDummy(2), new DestinationObjectDummy(1)]));
        $synchronizer = new Synchronizer($source, new MapperStub(), $destination);

        $synchronizer->synchronize('test');
    }

    /**
     * @test
     */
    public function skips_new_unmappable_source_objects()
    {
        $idOfSourceObject = 1;
        $newSourceObject = new SourceObjectDummy($idOfSourceObject);
        $this->setUpSourceToReturn(new \ArrayIterator([$newSourceObject]));

        $this->setUpDestinationToReturn(new \ArrayIterator());

        $newDestinationObject = new \stdClass();
        $this->destination->expects($this->once())
            ->method('createObject')
            ->with($idOfSourceObject, $this->className)
            ->willReturn($newDestinationObject);

        $this->mapper->method('map')->with($newSourceObject, $newDestinationObject)->willReturn(MapResult::unmappable());

        $this->destination->expects($this->never())->method('updated');

        // afterObjectProcessed() called for every object
        $this->destination->expects(self::once())->method('afterObjectProcessed');

        // commit() called at the end
        $this->destination->expects(self::once())->method('commit');

        $this->runSynchronize();
    }

    /**
     * @test
     */
    public function deletes_unmappable_source_objects()
    {
        $id = 1;
        $sourceObject = new SourceObjectDummy($id);
        $this->setUpSourceToReturn(new \ArrayIterator([$sourceObject]));

        $destinationObject = new DestinationObjectDummy($id);
        $this->setUpDestinationToReturn(new \ArrayIterator([$destinationObject]));

        $this->mapper->method('map')->willReturn(MapResult::unmappable());

        $this->destination->expects(self::once())->method('delete');

        // afterObjectProcessed() called for every object
        $this->destination->expects(self::once())->method('afterObjectProcessed');

        $this->destination->expects(self::once())->method('commit');

        $this->runSynchronize();
    }

    private function setUpSourceToReturn(\Iterator $sourceObjects)
    {
        $this->source
            ->method('getObjectsOrderedById')
            ->willReturn($sourceObjects);
    }

    private function setUpDestinationToReturn(\Iterator $destinationObjects)
    {
        $this->destination
            ->method('getObjectsOrderedById')
            ->willReturn($destinationObjects);
    }

    private function runSynchronize(bool $force = false): void
    {
        $synchronizer = new Synchronizer($this->source, $this->mapper, $this->destination);
        $synchronizer->synchronize($this->className, $force);
    }
}
