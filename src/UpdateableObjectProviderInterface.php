<?php

namespace Webfactory\ContentMapping;

/**
 * Additional interface a DestinationAdapter can implement if it wishes to have different objects
 * updated than those returned from the DestinationAdapter::getObjectsOrderedById method.
 *
 * For example, some "destination" systems return read-only objects from their query methods.
 * By implementing this interface, a destination adapter may return these read-only objects
 * from the DestinationAdapter::getObjectsOrderedById() method. In the case that an object
 * needs to be updated, the Synchronizer implementation will call the prepareUpdate() method,
 * pass in the read-only object and then use the returned object when calling the Mapper.
 *
 * Another advantage is that the objects being updated can be different from those contained
 * in the result set used internally by the destination system. This allows a more efficient
 * memory usage / GC, as the updated object (containing chunks of data that needs to be written
 * to the destination) can be pruned/GC'd after the update has been performed, while the
 * result set must be kept around until the entire process has finished.
 *
 * @psalm-template Tr of object
 * @psalm-template Tw of object
 */
interface UpdateableObjectProviderInterface
{
    /**
     * Create the object instance that can be used to update data in the target system.
     *
     * @param object $destinationObject A destination object as returned from getObjectsOrderedById()
     * @psalm-param Tr $destinationObject
     *
     * @return object The (possibly new) object that will be passed to the Mapper.
     * @psalm-return Tw
     */
    public function prepareUpdate($destinationObject);
}
