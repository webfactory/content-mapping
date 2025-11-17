<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping;

use Iterator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The Synchronizer synchronizes objects from a source system with these in a destination system.
 *
 * @psalm-template Ts of object
 * @psalm-template Tr of object
 * @psalm-template Tw of object
 * @psalm-api
 */
final class Synchronizer
{
    /**
     * @var SourceAdapter<Ts>
     */
    private $source;

    /**
     * @var Mapper<Ts, Tw>
     */
    private $mapper;

    /**
     * @var DestinationAdapter<Tr, Tw>
     */
    private $destination;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var ?int */
    private $lastSourceId;

    /** @var ?int */
    private $lastDestinationId;

    /**
     * @psalm-param SourceAdapter<Ts> $source
     * @psalm-param Mapper<Ts, Tw> $mapper
     * @psalm-param DestinationAdapter<Tr, Tw> $destination
     */
    public function __construct(
        SourceAdapter $source,
        Mapper $mapper,
        DestinationAdapter $destination,
        ?LoggerInterface $logger = null
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

    /**
     * @psalm-param Ts $sourceObject
     */
    private function fetchSourceId(object $sourceObject): int
    {
        $id = $this->mapper->idOf($sourceObject);

        if (null !== $this->lastSourceId && $id < $this->lastSourceId) {
            throw new ContentMappingException('Source IDs are out of order');
        }
        $this->lastSourceId = $id;

        return $id;
    }

    /**
     * @psalm-param Tr $destinationObject
     */
    private function fetchDestinationId(object $destinationObject): int
    {
        $id = $this->destination->idOf($destinationObject);

        if (null !== $this->lastDestinationId && $id < $this->lastDestinationId) {
            throw new ContentMappingException('Destination IDs are out of order');
        }
        $this->lastDestinationId = $id;

        return $id;
    }

    /**
     * @psalm-param Iterator<Ts> $sourceQueue
     * @psalm-param Iterator<Tr> $destinationQueue
     */
    private function compareQueuesAndReactAccordingly(\Iterator $sourceQueue, \Iterator $destinationQueue, string $className): void
    {
        while ($sourceQueue->valid() && $destinationQueue->valid()) {
            $sourceObject = $sourceQueue->current();
            if (null === $sourceObject) {
                throw new ContentMappingException('Source object is missing');
            }

            $destinationObject = $destinationQueue->current();
            if (null === $destinationObject) {
                throw new ContentMappingException('Destination object is missing');
            }

            $sourceObjectId = $this->fetchSourceId($sourceObject);
            $destinationObjectId = $this->fetchDestinationId($destinationObject);

            if ($destinationObjectId > $sourceObjectId) {
                $this->insert($className, $sourceObject);
                $sourceQueue->next();
            } elseif ($destinationObjectId < $sourceObjectId) {
                $this->delete($destinationObject);
                $destinationQueue->next();
            } else {
                if ($sourceObjectId != $destinationObjectId) {
                    throw new \LogicException();
                }
                $this->update($sourceObject, $destinationObject);
                $sourceQueue->next();
                $destinationQueue->next();
            }

            $this->notifyProgress();
        }
    }

    /**
     * @psalm-param Ts $sourceObject
     */
    private function insert(string $className, object $sourceObject): void
    {
        $newObjectInDestinationSystem = $this->destination->createObject(
            $this->mapper->idOf($sourceObject),
            $className
        );

        $mapResult = $this->mapper->map($sourceObject, $newObjectInDestinationSystem);

        if ($mapResult->isUnmappableResult()) {
            $this->logger->info('Skipped unmappable object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);

            return;
        }

        if (!$mapResult->getObjectHasChanged()) {
            return;
        }

        /** @var Tw */
        $result = $mapResult->getObject();
        $this->destination->updated($result);
        $this->logger->info('Inserted object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
    }

    /**
     * @psalm-param Tr $destinationObject
     */
    private function delete(object $destinationObject): void
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
     * @psalm-param Ts $sourceObject
     * @psalm-param Tr $destinationObject
     */
    private function update(object $sourceObject, object $destinationObject): void
    {
        if ($this->destination instanceof UpdateableObjectProviderInterface) {
            /** @var UpdateableObjectProviderInterface<Tr,Tw> */
            $dst = $this->destination;
            $updateableDestinationObject = $dst->prepareUpdate($destinationObject);
        } else {
            /** @var Tw */
            $updateableDestinationObject = $destinationObject;
        }

        $mapResult = $this->mapper->map($sourceObject, $updateableDestinationObject);

        if ($mapResult->isUnmappableResult()) {
            $this->destination->delete($destinationObject);
            $this->logger->info('Deleted unmappable object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);

            return;
        }

        if (!$mapResult->getObjectHasChanged()) {
            $this->logger->debug('Kept object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);

            return;
        }

        /** @var Tw */
        $result = $mapResult->getObject();

        $this->destination->updated($result);
        $this->logger->info('Updated object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
    }

    /**
     * @psalm-param Iterator<Ts> $sourceQueue
     */
    private function insertRemainingSourceObjects(\Iterator $sourceQueue, string $className): void
    {
        while ($sourceQueue->valid()) {
            $sourceObject = $sourceQueue->current();
            if (null === $sourceObject) {
                throw new ContentMappingException('Source object is missing');
            }

            $this->fetchSourceId($sourceObject);
            $this->insert($className, $sourceObject);
            $this->notifyProgress();
            $sourceQueue->next();
        }
    }

    /**
     * @psalm-param Iterator<Tr> $destinationQueue
     */
    private function deleteRemainingDestinationObjects(\Iterator $destinationQueue): void
    {
        while ($destinationQueue->valid()) {
            $destinationObject = $destinationQueue->current();
            if (null === $destinationObject) {
                throw new ContentMappingException('Destination object is missing');
            }

            $this->fetchDestinationId($destinationObject);
            $this->delete($destinationObject);
            $this->notifyProgress();
            $destinationQueue->next();
        }
    }

    private function notifyProgress(): void
    {
        if ($this->destination instanceof ProgressListenerInterface) {
            $this->destination->afterObjectProcessed();
        }
    }
}
