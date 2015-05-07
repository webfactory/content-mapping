<?php

namespace Webfactory\ContentMapping;

use Iterator;

/**
 * Adapter for a source system (e.g. Propel) containing objects that should be mapped into a destination system (e.g. a
 * Solr index).
 */
interface SourceAdapter
{
    /**
     * Get an Iterator over all objects in the source system, ordered by their ascending IDs.
     *
     * @return Iterator
     */
    public function getObjectsOrderedById();
}
