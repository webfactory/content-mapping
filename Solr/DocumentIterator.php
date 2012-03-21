<?php

namespace Webfactory\ContentMapping\Solr;

/*
 * Iteriert ein Array von Apache_Solr_Documents und wrapped jedes einzelne dabei
 * in einen MappableWrapper.
 */
class DocumentIterator extends \ArrayIterator {

    public function __construct(array $apacheSolrDocuments) {
        parent::__construct($apacheSolrDocuments);
    }

    public function current() {
        return new MappableWrapper(parent::current());
    }

}