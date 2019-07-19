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

    public function createObject($id, $className)
    {
        return new DestinationObjectDummy();
    }

    public function delete($objectInDestinationSystem)
    {
    }

    public function updated($objectInDestinationSystem)
    {
    }

    public function commit()
    {
    }

    public function idOf($objectInDestinationSystem)
    {
        return $objectInDestinationSystem->getId();
    }
}
