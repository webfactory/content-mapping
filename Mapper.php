<?php

namespace Webfactory\ContentMapping;

/**
 * Maps objects frm a source system into objects of a destination system.
 */
interface Mapper
{
    /**
     * Takes care of mapping the content from the $sourceEntity into the $destinationEntity.
     *
     * What exactly these entities are depends on the source and destination system. The objects
     * passed in will be those returned from the SourceAdapter::getObjectsOrderedById() and
     * DestinationAdapter::getObjectsOrderedById() methods.
     *
     * @param mixed $sourceEntity
     * @param mixed $destinationEntity
     *
     * @return boolean Returns true when the $destinationEntity was updated and needs to be written to the destination system; false otherwise.
     */
    public function map($sourceEntity, $destinationEntity);

    /**
     * Get the id of an object in the source system.
     *
     * @param mixed $sourceEntity
     *
     * @return int
     */
    public function idOf($sourceEntity);

    /**
     * Sets whether to force an update on the target object, even if the source has not changed.
     *
     * @param bool $force
     */
    public function setForce($force);
}
