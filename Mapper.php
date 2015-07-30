<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping;

/**
 * Maps objects from a source system into objects of a destination system.
 */
interface Mapper
{
    /**
     * Maps the content of the $sourceObject into the $destinationObject.
     *
     * What exactly these objects are depends on the source and destination system. The objects
     * passed in will be those returned from the SourceAdapter::getObjectsOrderedById() and
     * DestinationAdapter::getObjectsOrderedById() methods.
     *
     * @param mixed $sourceObject
     * @param mixed $destinationObject
     * @return boolean Returns true when the $destinationObject was updated and needs to be written to the destination
     * system; false otherwise.
     */
    public function map($sourceObject, $destinationObject);

    /**
     * Get the id of an object in the source system.
     *
     * @param mixed $sourceObject
     * @return int
     */
    public function idOf($sourceObject);

    /**
     * Sets whether to force an update on the target object, even if the source has not changed.
     *
     * @param bool $force
     */
    public function setForce($force);
}
