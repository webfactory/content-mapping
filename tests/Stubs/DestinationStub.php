<?php

namespace Webfactory\ContentMapping\Test\Stubs;

use Webfactory\ContentMapping\DestinationAdapter;

class DestinationStub implements DestinationAdapter
{
    /**
     * @var \Iterator
     */
    private $objects;

    /**
     * @param $objects
     */
    public function __construct(\Iterator $objects = null)
    {
        $this->objects = $objects ?: new \ArrayIterator();
    }

    public function getObjectsOrderedById($className)
    {
        return $this->objects;
    }

    public function createObject(int $id, string $className)
    {
        return new DestinationObjectDummy($id);
    }

    public function delete($objectInDestinationSystem): void
    {
    }

    public function updated($objectInDestinationSystem): void
    {
    }

    public function commit(): void
    {
    }

    public function idOf($objectInDestinationSystem): int
    {
        return $objectInDestinationSystem->getId();
    }
}
