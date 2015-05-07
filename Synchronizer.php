<?php

namespace Webfactory\ContentMapping;

use Psr\Log\LoggerInterface;

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
     * @var \Iterator
     */
    private $sourceQueue;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var DestinationAdapter
     */
    private $destination;

    /**
     * @var \Iterator
     */
    private $destinationQueue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string value of the synchronize() parameter
     */
    private $className;

    /**
     * @param SourceAdapter $source
     * @param Mapper $mapper
     * @param DestinationAdapter $destination
     * @param LoggerInterface $logger
     */
    public function __construct(
        SourceAdapter $source,
        Mapper $mapper,
        DestinationAdapter $destination,
        LoggerInterface $logger
    ) {
        $this->source = $source;
        $this->mapper = $mapper;
        $this->destination = $destination;
        $this->logger = $logger;
    }

    /**
     * Synchronizes the $className objects from the source system to the destination system.
     *
     * @param string $className
     */
    public function synchronize($className)
    {
        $this->className = $className;
        $this->sourceQueue = $this->source->getObjectsOrderedById();
        $this->destinationQueue = $this->destination->getObjectsOrderedById($className);

        while ($this->sourceQueue->valid() && $this->destinationQueue->valid()) {
            $this->compareQueuesAndReactAccordingly();
        }

        $this->insertRemainingSourceObjects();
        $this->deleteRemainingDestinationObjects();

        $this->destination->commit();
    }

    private function compareQueuesAndReactAccordingly()
    {
        $sourceObject = $this->sourceQueue->current();
        $sourceObjectId = $this->mapper->idOf($sourceObject);
        $destinationObject = $this->destinationQueue->current();
        $destinationObjectId = $this->destination->idOf($destinationObject);

        if ($destinationObjectId > $sourceObjectId) {
            $this->insert($sourceObject);
        } elseif ($destinationObjectId < $sourceObjectId) {
            $this->delete($destinationObject);
        } elseif ($destinationObjectId === $sourceObjectId) {
            // if mapper->map() === true -> updated
        } else {
            $this->destinationQueue->next();
            $this->sourceQueue->next();
        }
    }

    /**
     * @param mixed $sourceObject
     */
    private function insert($sourceObject)
    {
        $newObjectInDestinationSystem = $this->destination->createObject(
            $this->mapper->idOf($sourceObject),
            $this->className
        );
        $this->mapper->map($sourceObject, $newObjectInDestinationSystem);

        $this->sourceQueue->next();
    }

    /**
     * @param mixed $destinationObject
     */
    private function delete($destinationObject)
    {
        $this->destinationQueue->next();
    }

    private function insertRemainingSourceObjects()
    {
        while ($this->sourceQueue->valid()) {
            $this->insert($this->sourceQueue->current());
        }
    }

    private function deleteRemainingDestinationObjects()
    {
        while ($this->destinationQueue->valid()) {
            $this->delete($this->destinationQueue->current());
        }
    }
}
