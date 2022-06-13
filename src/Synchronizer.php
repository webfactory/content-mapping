<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping;

use Iterator;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The Synchronizer synchronizes objects from a source system with these in a destination system.
 *
 * @final by default.
 */
final class Synchronizer
{
    /**
     * @var SourceAdapter
     */
    private $source;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var DestinationAdapter
     */
    private $destination;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $lastSourceId;

    private $lastDestinationId;

    public function __construct(
        SourceAdapter $source,
        Mapper $mapper,
        DestinationAdapter $destination,
        LoggerInterface $logger = null
    ) {
        $this->source = $source;
        $this->mapper = $mapper;
        $this->destination = $destination;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Synchronizes the $className objects from the source system to the destination system.
     */
    public function synchronize(string $className, bool $force = false): void
    {
        $this->logger->notice(
            'Start of '.($force ? 'forced ' : '').'synchronization for {className}.',
            ['className' => $className]
        );

        $this->mapper->setForce($force);

        $this->lastSourceId = null;
        $this->lastDestinationId = null;

        $sourceQueue = $this->source->getObjectsOrderedById();
        $sourceQueue->rewind();

        $destinationQueue = $this->destination->getObjectsOrderedById($className);
        $destinationQueue->rewind();

        $this->compareQueuesAndReactAccordingly($sourceQueue, $destinationQueue, $className);
        $this->insertRemainingSourceObjects($sourceQueue, $className);
        $this->deleteRemainingDestinationObjects($destinationQueue);

        $this->destination->commit();
        $this->logger->notice('End of synchronization for {className}.', ['className' => $className]);
    }

    private function fetchSourceId($sourceObject)
    {
        $id = $this->mapper->idOf($sourceObject);

        if ($this->lastSourceId && $id < $this->lastSourceId) {
            throw new ContentMappingException('Source IDs are out of order');
        }
        $this->lastSourceId = $id;

        return $id;
    }

    private function fetchDestinationId($destinationObject)
    {
        $id = $this->destination->idOf($destinationObject);

        if ($this->lastDestinationId && $id < $this->lastDestinationId) {
            throw new ContentMappingException('Destination IDs are out of order');
        }
        $this->lastDestinationId = $id;

        return $id;
    }

    private function compareQueuesAndReactAccordingly(Iterator $sourceQueue, Iterator $destinationQueue, string $className)
    {
        while ($sourceQueue->valid() && $destinationQueue->valid()) {
            $sourceObject = $sourceQueue->current();
            $sourceObjectId = $this->fetchSourceId($sourceObject);

            $destinationObject = $destinationQueue->current();
            $destinationObjectId = $this->fetchDestinationId($destinationObject);

            if ($destinationObjectId > $sourceObjectId) {
                $this->insert($className, $sourceObject);
                $sourceQueue->next();
            } elseif ($destinationObjectId < $sourceObjectId) {
                $this->delete($destinationObject);
                $destinationQueue->next();
            } else {
                if ($sourceObjectId != $destinationObjectId) {
                    throw new LogicException();
                }
                $this->update($sourceObject, $destinationObject);
                $sourceQueue->next();
                $destinationQueue->next();
            }

            $this->notifyProgress();
        }
    }

    /**
     * @param mixed $sourceObject
     */
    private function insert(string $className, $sourceObject)
    {
        $newObjectInDestinationSystem = $this->destination->createObject(
            $this->mapper->idOf($sourceObject),
            $className
        );

        $mapResult = $this->mapper->map($sourceObject, $newObjectInDestinationSystem);

        if ($mapResult->isUnmappableResult()) {
            $this->logger->info('Skipped unmappable object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        } else {
            $this->destination->updated($mapResult->getObject());
            $this->logger->info('Inserted object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        }
    }

    /**
     * @param mixed $destinationObject
     */
    private function delete($destinationObject)
    {
        $this->destination->delete($destinationObject);
        $this->logger->info(
            'Deleted object with id {id}.',
            [
                'id' => $this->destination->idOf($destinationObject),
            ]
        );
    }

    /**
     * @param mixed $sourceObject
     * @param mixed $destinationObject
     */
    private function update($sourceObject, $destinationObject)
    {
        if ($this->destination instanceof UpdateableObjectProviderInterface) {
            $updateableDestinationObject = $this->destination->prepareUpdate($destinationObject);
        } else {
            $updateableDestinationObject = $destinationObject;
        }

        $mapResult = $this->mapper->map($sourceObject, $updateableDestinationObject);

        if ($mapResult->isUnmappableResult()) {
            $this->destination->delete($destinationObject);
            $this->logger->info('Deleted unmappable object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        } elseif ($mapResult->getObjectHasChanged()) {
            $this->destination->updated($mapResult->getObject());
            $this->logger->info('Updated object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        } else {
            $this->logger->info('Kept object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        }
    }

    private function insertRemainingSourceObjects(Iterator $sourceQueue, string $className)
    {
        while ($sourceQueue->valid()) {
            $sourceObject = $sourceQueue->current();
            $this->fetchSourceId($sourceObject);
            $this->insert($className, $sourceObject);
            $this->notifyProgress();
            $sourceQueue->next();
        }
    }

    private function deleteRemainingDestinationObjects(Iterator $destinationQueue)
    {
        while ($destinationQueue->valid()) {
            $destinationObject = $destinationQueue->current();
            $this->fetchDestinationId($destinationObject);
            $this->delete($destinationObject);
            $this->notifyProgress();
            $destinationQueue->next();
        }
    }

    private function notifyProgress()
    {
        if ($this->destination instanceof ProgressListenerInterface) {
            $this->destination->afterObjectProcessed();
        }
    }
}
