<?php

namespace Webfactory\ContentMapping\Test\Stubs;

use Webfactory\ContentMapping\SourceAdapter;

class SourceStub implements SourceAdapter
{
    /**
     * @var \Iterator
     */
    private $objects;

    public function __construct(?\Iterator $objects = null)
    {
        $this->objects = $objects ?: new \ArrayIterator();
    }

    public function getObjectsOrderedById()
    {
        return $this->objects;
    }
}
