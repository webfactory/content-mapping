<?php

namespace Webfactory\ContentMapping\Propel;

use Webfactory\ContentMapping\Solr\Mappable;
use Monolog\Logger;
use Webfactory\PdfTextExtraction\Extraction;

abstract class SolrMapper implements Mappable {

    protected $object;
    protected $peer;
    protected $solrDocument;
    protected $log;
    protected $textExtraction;

    public function __construct(\BaseObject $o, Logger $log, Extraction $textExtraction) {
        $this->object = $o;
        $this->peer = $o->getPeer(); // nicht in BaseObject garantiert, aber in Base* vorhanden
        $this->log = $log;
        $this->textExtraction = $textExtraction;
    }

    public function getObjectId() {
        return $this->object->getId();
    }

    public function mapToSolrDocument(\Apache_Solr_Document $document) {
        $this->solrDocument = $document;
        $this->applyMappings();
        $this->solrDocument = null;
    }

    abstract protected function applyMappings();

    protected function mapDate($solrFieldname, $propelFieldname, $object = null) {
        if ($date = $this->getObjectProperty($propelFieldname, $object, array(self::SOLR_DATE_FORMAT))) {
            $this->setValue($solrFieldname, $date);
        }
    }

    protected function mapText($solrFieldname, $propelFieldname, $object = null) {
        // strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        $v = $this->getObjectProperty($propelFieldname, $object);
        $v = str_replace('<p>', ' ', $v);
        $v = strip_tags($v);
        $this->setValue($solrFieldname, $v);
    }

    protected function mapBinaryExtract($solrFieldname, $propelFieldname, $object = null) {
        // 	strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        if ($file = $this->getObjectProperty($propelFieldname, $object)) {

            $obj = $object ? $object : $this->object;
            $start = microtime(true);
            $this->log->debug("Running Pdf\TextExtraction for field $propelFieldname on " . get_class($obj) . ":" . $obj->getId());

            $v = $this->textExtraction->extract($file->getContents());
            $v = str_replace('<p>', ' ', $v);
            $v = strip_tags($v);

            $this->log->debug("Finished Pdf\TextExtraction");
            $this->log->notice(sprintf("Text extraction took %.2f seconds", microtime(true)-$start));

            $this->setValue($solrFieldname, $v);
        }
    }

    protected function setValue($solrFieldname, $value) {
        $this->solrDocument->setField($solrFieldname, $value);
    }

    protected function setMultiValue($solrFieldname, $value) {
        $this->solrDocument->setMultiValue($solrFieldname, $value);
    }

    protected function getObjectProperty($propelFieldname, $object = null, array $parameters = array()) {
        if ($object === null) {
            $object = $this->object;
            $peer = $this->peer;
        } else {
            $peer = $object->getPeer();
        }

        try {
            $method = 'get' . $peer->translateFieldname($propelFieldname, \BasePeer::TYPE_COLNAME, \BasePeer::TYPE_PHPNAME);
        } catch (\PropelException $e) {
            // fallback:
            $method = 'get' . ucfirst($propelFieldname);
        }

        return call_user_func_array(array($object, $method), $parameters);
    }

    protected function boost($boost) {
        $this->solrDocument->setBoost($boost);
    }

}
