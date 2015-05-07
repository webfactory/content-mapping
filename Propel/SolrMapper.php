<?php

namespace Webfactory\ContentMapping\Propel;

use Psr\Log\LoggerInterface;
use Webfactory\ContentMapping\Solr\Mappable;
use Webfactory\PdfTextExtraction\Extraction;

abstract class SolrMapper implements Mappable
{
    /**
     * @var \BaseObject
     */
    protected $object;

    /**
     * @var mixed
     */
    protected $peer;

    /**
     * @var \Apache_Solr_Document
     */
    protected $solrDocument;

    /**
     * @var Extraction
     */
    protected $textExtraction;

    /**
     * @var LoggerInterface
     */
    protected $log;

    public function __construct(\BaseObject $o, LoggerInterface $logger) {
        $this->object = $o;
        $this->peer = $o->getPeer(); // nicht in BaseObject garantiert, aber in Base* vorhanden
        $this->log = $logger;
    }

    public function setExtraction(Extraction $textExtraction) {
        $this->textExtraction = $textExtraction;
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectId() {
        return $this->object->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function mapToSolrDocument(\Apache_Solr_Document $document) {
        $this->solrDocument = $document;
        $this->applyMappings();
        $this->solrDocument = null;
    }

    abstract protected function applyMappings();

    /**
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $object
     */
    protected function mapDate($solrFieldname, $propelFieldname, $object = null) {
        if ($date = $this->getObjectProperty($propelFieldname, $object, array(self::SOLR_DATE_FORMAT))) {
            $this->setValue($solrFieldname, $date);
        }
    }

    /**
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $object
     */
    protected function mapText($solrFieldname, $propelFieldname, $object = null) {
        // strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        $v = $this->getObjectProperty($propelFieldname, $object);
        $v = str_replace('<p>', ' ', $v);
        $v = strip_tags($v);
        $this->setValue($solrFieldname, $v);
    }

    /**
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $object
     */
    protected function mapBinaryExtract($solrFieldname, $propelFieldname, $object = null) {
        // 	strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        if ($file = $this->getObjectProperty($propelFieldname, $object)) {

            if (!$this->textExtraction) {
                $this->log->err('PdfTextExtraction requested but no Extraction available for ' . get_class($this));
                return;
            }


            $obj = $object ? $object : $this->object;
            $start = microtime(true);
            $this->log->debug("Running PdfTextExtraction for field $propelFieldname on " . get_class($obj) . ":" . $obj->getId());

            $v = $this->textExtraction->extract($file->getContents());
            $v = str_replace('<p>', ' ', $v);
            $v = strip_tags($v);

            $this->log->debug("Finished PdfTextExtraction");
            $this->log->notice(sprintf("Text extraction took %.2f seconds", microtime(true)-$start));

            $this->setValue($solrFieldname, $v);
        }
    }

    /**
     * @param string $solrFieldname
     * @param mixed $value
     */
    protected function setValue($solrFieldname, $value) {
        $this->solrDocument->setField($solrFieldname, $value);
    }

    /**
     * @param string $solrFieldname
     * @param mixed $value
     */
    protected function setMultiValue($solrFieldname, $value) {
        $this->solrDocument->setMultiValue($solrFieldname, $value);
    }

    /**
     * @param string $propelFieldname
     * @param mixed|null $object
     * @param array $parameters
     * @return mixed
     */
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

    /**
     * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
     */
    protected function boost($boost) {
        $this->solrDocument->setBoost($boost);
    }
}
