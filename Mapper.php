<?php

namespace Webfactory\ContentMapping;

/**
 * Maps objects frm a source system into objects of a destination system.
 */
interface Mapper
{
    /**
     * Maps an $objectInSourceSystem into an $objectInDestinationSystem, especially by copying and converting it's
     * values.
     *
     * @param mixed $objectInSourceSystem
     * @param mixed $objectInDestinationSystem
     * @return boolean whether the $objectInDestinationSystem has changed
     */
    public function map($objectInSourceSystem, $objectInDestinationSystem);

    /**
     * Get the id of an object in the source system.
     *
     * @param mixed $objectInSourceSystem
     * @return int
     */
    public function idOf($objectInSourceSystem);
}
