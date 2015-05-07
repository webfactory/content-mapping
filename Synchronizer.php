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
    }
}
