<?php

namespace Webfactory\ContentMapping\Test\Stubs;

use Webfactory\ContentMapping\Mapper;
use Webfactory\ContentMapping\MapResult;

class MapperStub implements Mapper
{
    public function map($sourceObject, $destinationObject)
    {
        return new MapResult($destinationObject, true);
    }

    public function idOf($sourceObject)
    {
        return $sourceObject->getId();
    }

    public function setForce($force)
    {
    }
}
