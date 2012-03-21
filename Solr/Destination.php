<?php

namespace Webfactory\ContentMapping\Solr;

use Webfactory\ContentMapping\Destination as BaseDestination;
use Webfactory\ContentMapping\Mappable as BaseMappable;
use Monolog\Logger;

/*
 * Stellt einen Apache_Solr_Service als ContentMappingDestination dar.
 * Die ContentMappables der gegenübergestellten Source müssen SolrMappable sein,
 * damit es Sinn ergibt.
 */
class Destination implements BaseDestination {

    protected $solrService;
    protected $log;

    protected $newOrUpdatedDocuments = array();
    protected $deletedDocumentIds = array();

    public function __construct(\Apache_Solr_Service $solrService) {
        $this->solrService = $solrService;
    }

    public function setLogger(Logger $log) {
        $this->log = $log;
    }

    public function getObjectIterator($objectClass) {

        $res = $this->solrService->search(
                '{!raw f=objectclass}' . $objectClass,
                0,
                1000000,
                array(
                    'fl' => 'id,objectid,objectclass,hash',
                    'sort' => 'objectid asc'
                )
            )->response;

        $this->log->notice("Solr\Destination found {$res->numFound} objects for objectClass $objectClass");

        return new DocumentIterator(
            $res->docs
        );
    }

    protected function map(Mappable $sourceObject, \Apache_Solr_Document $into) {
        try {
            $into->setField('hash', $sourceObject->getHash());
            $sourceObject->mapToSolrDocument($into);
            $this->newOrUpdatedDocuments[] = $into;
            $this->possiblyFlush();
        } catch (\Exception $e) {
            $this->log->err("Exception during mapping of source object #{$sourceObject->getObjectId()}, SKIPPING! The exception was as follows:\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    public function insert($objectClass, BaseMappable $sourceObject) {

        if (!$sourceObject instanceof Mappable)
            throw new \Exception('SourceObject is not a Solr\Mappable');

        $document = new \Apache_Solr_Document();
        $document->setField('id', $objectClass . ':' . $sourceObject->getObjectId());
        $document->setField('objectid', $sourceObject->getObjectId());
        $document->setField('objectclass', $objectClass);
        $this->map($sourceObject, $document);
    }

    public function update(BaseMappable $destinationObject, BaseMappable $sourceObject) {
        if (!$destinationObject instanceof MappableWrapper)
            throw new \Exception('DestinationObject is not a Solr\MappableWrapper');

        if (!$sourceObject instanceof Mappable)
            throw new \Exception('SourceObject is not a Solr\Mappable');

        $this->map($sourceObject, clone $destinationObject->getSolrDocument());
    }

    public function delete(BaseMappable $destinationObject) {
        if (!$destinationObject instanceof MappableWrapper)
            throw new \Exception('DestinationObject is not a Solr\MappableWrapper');

        $f = $destinationObject->getSolrDocument()->getField('id');
        $this->deletedDocumentIds[] = $f['value'];
        $this->possiblyFlush();
    }

    public function finish() {
        $this->flush();

        $this->log->notice("Solr commit and optimize");
        $this->solrService->commit(); // macht auch ein optimize()
        $this->log->notice("Finished commit");
    }

    protected function possiblyFlush() {
        if ((count($this->deletedDocumentIds) + count($this->newOrUpdatedDocuments)) >= 20)
            $this->flush();
    }

    protected function flush() {
        if ($this->deletedDocumentIds || $this->newOrUpdatedDocuments) {
            $this->log->notice("Flushing " . count($this->newOrUpdatedDocuments) . " inserts or updates and " . count($this->deletedDocumentIds) . " deletes");
            if ($this->deletedDocumentIds) $this->solrService->deleteByMultipleIds($this->deletedDocumentIds);
            if ($this->newOrUpdatedDocuments) $this->solrService->addDocuments($this->newOrUpdatedDocuments);
            $this->deletedDocumentIds = array();
            $this->newOrUpdatedDocuments = array();
            $this->log->notice("Flushed");
        }
    }

}
