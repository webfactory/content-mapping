<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping;

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

    private $lastSourceId;

    private $lastDestinationId;

    /**
     * @param SourceAdapter      $source
     * @param Mapper             $mapper
     * @param DestinationAdapter $destination
     * @param LoggerInterface    $logger
     */
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
     *
     * @param string $className
     * @param bool   $force
     */
    public function synchronize($className, $force = false)
    {
        $this->logger->notice(
            'Start of '.($force ? 'forced ' : '').'synchronization for {className}.',
            ['className' => $className]
        );

        $this->className = $className;
        $this->mapper->setForce($force);

        $this->sourceQueue = $this->source->getObjectsOrderedById();
        $this->sourceQueue->rewind();

        $this->destinationQueue = $this->destination->getObjectsOrderedById($className);
        $this->destinationQueue->rewind();

        while ($this->sourceQueue->valid() && $this->destinationQueue->valid()) {
            $this->compareQueuesAndReactAccordingly();
        }

        $this->insertRemainingSourceObjects();
        $this->deleteRemainingDestinationObjects();

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

    private function compareQueuesAndReactAccordingly()
    {
        $sourceObject = $this->sourceQueue->current();
        $sourceObjectId = $this->fetchSourceId($sourceObject);

        $destinationObject = $this->destinationQueue->current();
        $destinationObjectId = $this->fetchDestinationId($destinationObject);

        if ($destinationObjectId > $sourceObjectId) {
            $this->insert($sourceObject);
        } elseif ($destinationObjectId < $sourceObjectId) {
            $this->delete($destinationObject);
        } elseif ($destinationObjectId === $sourceObjectId) {
            $this->update($sourceObject, $destinationObject);
        } else {
            $this->destinationQueue->next();
            $this->sourceQueue->next();
        }

        $this->notifyProgress();
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

        $mapResult = $this->mapper->map($sourceObject, $newObjectInDestinationSystem);
        $this->destination->updated($mapResult->getObject());

        $this->sourceQueue->next();
        $this->logger->info('Inserted object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
    }

    /**
     * @param mixed $destinationObject
     */
    private function delete($destinationObject)
    {
        $this->destination->delete($destinationObject);
        $this->destinationQueue->next();
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
            $destinationObject = $this->destination->prepareUpdate($destinationObject);
        }

        $mapResult = $this->mapper->map($sourceObject, $destinationObject);

        if (true === $mapResult->getObjectHasChanged()) {
            $this->destination->updated($mapResult->getObject());
            $this->logger->info('Updated object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        } else {
            $this->logger->info('Kept object with id {id}.', ['id' => $this->mapper->idOf($sourceObject)]);
        }

        $this->destinationQueue->next();
        $this->sourceQueue->next();
    }

    private function insertRemainingSourceObjects()
    {
        while ($this->sourceQueue->valid()) {
            $sourceObject = $this->sourceQueue->current();
            $this->fetchSourceId($sourceObject);
            $this->insert($sourceObject);
            $this->notifyProgress();
        }
    }

    private function deleteRemainingDestinationObjects()
    {
        while ($this->destinationQueue->valid()) {
            $destinationObject = $this->destinationQueue->current();
            $this->fetchDestinationId($destinationObject);
            $this->delete($destinationObject);
            $this->notifyProgress();
        }
    }

    private function notifyProgress()
    {
        if ($this->destination instanceof ProgressListenerInterface) {
            $this->destination->afterObjectProcessed();
        }
    }
}
