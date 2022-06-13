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
 * Adapter for a source system (e.g. Propel) containing objects that should be mapped into a destination system (e.g. a
 * Solr index).
 *
 * @psalm-template Ts of object
 */
interface SourceAdapter
{
    /**
     * Get an Iterator over all objects in the source system, ordered by their ascending IDs.
     *
     * @return Iterator
     * @psalm-return Iterator<Ts>
     */
    public function getObjectsOrderedById();
}
