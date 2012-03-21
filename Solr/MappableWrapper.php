<?php

namespace Webfactory\ContentMapping\Solr;

use Webfactory\ContentMapping\Mappable;

/*
 * Passt Apache_Solr_Documents (aus der SolrDestination) auf das
 * ContentMappable-Interface an.
 */
class MappableWrapper implements Mappable {

    protected $solrDocument;

    public function __construct(\Apache_Solr_Document $solrDocument) {
        $this->solrDocument = $solrDocument;
    }

    public function getObjectId() {
        return $this->getFieldValue('objectid');
    }

    public function getHash() {
        return $this->getFieldValue('hash');
    }

    public function getSolrDocument() {
        return $this->solrDocument;
    }

    protected function getFieldValue($name) {
        $field = $this->solrDocument->getField($name);
        return $field['value'];
    }

}