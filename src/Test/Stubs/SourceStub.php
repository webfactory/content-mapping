<?php

namespace Webfactory\ContentMapping\Test\Stubs;

use Webfactory\ContentMapping\SourceAdapter;

class SourceStub implements SourceAdapter
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

    public function getObjectsOrderedById()
    {
        usort($entities, function($a, $b) {
            return $a->getId() <=> $b->getId();
        });

        return $this->objects;
    }
}
