<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping;

use Iterator;

/**
 * @psalm-template Tr of object
 * @psalm-template Tw of object
 */
interface DestinationAdapter
{
    /**
     * Get an Iterator over all $className objects in the destination system, ordered by their ascending IDs.
     *
     * @return Iterator
     *
     * @psalm-return Iterator<Tr>
     */
    public function getObjectsOrderedById(string $className);

    /**
     * Create a new object in the target system identified by ($id and $className).
     *
     * @return object
     *
     * @psalm-return Tw
     */
    public function createObject(int $id, string $className);

    /**
     * Delete the $object from the target system.
     *
     * @psalm-param Tr $objectInDestinationSystem
     *
     * @return void
     */
    public function delete(object $objectInDestinationSystem);

    /**
     * This method is a hook e.g. to notice an external change tracker that the $object has been updated.
     *
     * Although the name is somewhat misleading, it will be called after the Mapper has processed
     *   a) new objects created by the createObject() method
     *   b) changed objects created by the prepareUpdate() method *only if* the object actually changed.
     *
     * @psalm-param Tw $objectInDestinationSystem
     *
     * @return void
     */
    public function updated(object $objectInDestinationSystem);

    /**
     * This method is a hook e.g. to notice an external change tracker that all the in memory synchronization is
     * finished, i.e. can be persisted (e.g. by calling an entity manager's flush()).
     *
     * @return void
     */
    public function commit();

    /**
     * Get the id of an object in the destination system.
     *
     * @psalm-param Tr $objectInDestinationSystem
     *
     * @return int
     */
    public function idOf(object $objectInDestinationSystem);
}
