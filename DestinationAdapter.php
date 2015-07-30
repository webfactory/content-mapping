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
 * Adapter for a destination system (e.g. a Solr-Index) that will contain mappings of the objects in a source system
 * (e.g. a Propel managed database).
 */
interface DestinationAdapter
{
    /**
     * Get an Iterator over all $className objects in the destination system, ordered by their ascending IDs.
     *
     * @param string $className
     * @return Iterator
     */
    public function getObjectsOrderedById($className);

    /**
     * Create a new object in the target system identified by ($id and $className).
     *
     * @param int $id
     * @param string $className
     * @return mixed
     */
    public function createObject($id, $className);

    /**
     * Delete the $object from the target system.
     *
     * @param mixed $objectInDestinationSystem
     */
    public function delete($objectInDestinationSystem);

    /**
     * This method is a hook e.g. to notice an external change tracker that the $object has been updated.
     *
     * @param mixed $objectInDestinationSystem
     */
    public function updated($objectInDestinationSystem);

    /**
     * This method is a hook e.g. to notice an external change tracker that all the in memory synchronization is
     * finished, i.e. can be persisted (e.g. by calling an entity manager's flush()).
     */
    public function commit();

    /**
     * Get the id of an object in the destination system.
     *
     * @param mixed $objectInDestinationSystem
     * @return int
     */
    public function idOf($objectInDestinationSystem);
}
