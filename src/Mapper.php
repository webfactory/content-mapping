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
 *
 * @psalm-template Ts of object
 * @psalm-template Tw of object
 */
interface Mapper
{
    /**
     * Maps the content of the $sourceObject into the $destinationObject.
     *
     * @psalm-param Ts $sourceObject
     * @psalm-param Tw $destinationObject
     *
     * @return MapResult<Tw>
     */
    public function map(object $sourceObject, object $destinationObject);

    /**
     * Get the id of an object in the source system.
     *
     * @psalm-param Ts $sourceObject
     *
     * @return int
     */
    public function idOf(object $sourceObject);

    /**
     * Sets whether to force an update on the target object, even if the source has not changed.
     *
     * @return void
     */
    public function setForce(bool $force);
}
