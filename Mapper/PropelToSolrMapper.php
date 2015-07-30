<?php

namespace Webfactory\ContentMapping\Mapper;

use Psr\Log\LoggerInterface;
use Webfactory\ContentMapping\Mapper;
use Webfactory\PdfTextExtraction\Extraction;

/**
 * Legacy helper to map objects from a Propel source to a Solr destination.
 */
abstract class PropelToSolrMapper implements Mapper
{
    const SOLR_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    /**
     * @var mixed Propel source object.
     */
    protected $object;

    /**
     * Peer of the Propel source object.
     *
     * @var mixed
     */
    protected $peer;

    /**
     * @var \Apache_Solr_Document
     */
    protected $solrDocument;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var Extraction
     */
    protected $textExtraction;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param LoggerInterface $logger
     * @param Extraction $extraction
     */
    public function __construct(LoggerInterface $logger = null, Extraction $extraction = null)
    {
        $this->log = $logger;
        $this->textExtraction = $extraction;
    }

    /**
     * @param mixed $sourceObject
     * @return int
     */
    public function idOf($sourceObject)
    {
        return $sourceObject->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    /**
     * This implementation is a wrapper for legacy Mappers.
     *
     * @param mixed $sourceObject Propel source object
     * @param \Apache_Solr_Document $destinationObject
     * @return boolean whether the $objectInDestinationSystem has changed
     */
    public function map($sourceObject, $destinationObject)
    {
        $this->object = $sourceObject;
        $this->peer = $this->object->getPeer();
        $this->solrDocument = $destinationObject;
        $solrDocumentHasChanged = $this->applyMappings();
        $this->peer = null;
        $this->object = null;
        $this->solrDocument = null;

        return $solrDocumentHasChanged;
    }

    /**
     * Template method for doing work in map().
     *
     * @return boolean whether the $objectInDestinationSystem has changed
     */
    abstract protected function applyMappings();

    /**
     * Helper method for mapping dates.
     *
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $propelObject
     */
    protected function mapDate($solrFieldname, $propelFieldname, $propelObject = null)
    {
        if ($date = $this->getObjectProperty($propelFieldname, $propelObject, array(self::SOLR_DATE_FORMAT))) {
            $this->setValue($solrFieldname, $date);
        }
    }

    /**
     * Helper method for mapping texts.
     *
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $propelObject
     */
    protected function mapText($solrFieldname, $propelFieldname, $propelObject = null)
    {
        // strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        $v = $this->getObjectProperty($propelFieldname, $propelObject);
        $v = str_replace('<p>', ' ', $v);
        $v = strip_tags($v);
        $this->setValue($solrFieldname, $v);
    }

    /**
     * Helper method for mapping text that has yet to be extracted from binary data.
     *
     * @param string $solrFieldname
     * @param string $propelFieldname
     * @param mixed|null $propelObject
     */
    protected function mapBinaryExtract($solrFieldname, $propelFieldname, $propelObject = null)
    {
        // 	strip_tags als workaround für https://issues.apache.org/jira/browse/SOLR-42, evtl. hier am falschen Platz?
        if ($file = $this->getObjectProperty($propelFieldname, $propelObject)) {

            if (!$this->textExtraction) {
                $this->log->err('PdfTextExtraction requested but no Extraction available for ' . get_class($this));
                return;
            }

            $obj = $propelObject ? $propelObject : $this->object;
            $start = microtime(true);
            $this->log->debug(
                "Running PdfTextExtraction for field $propelFieldname on " . get_class($obj) . ":" . $obj->getId()
            );

            $v = $this->textExtraction->extract($file->getContents());
            $v = str_replace('<p>', ' ', $v);
            $v = strip_tags($v);

            $this->log->info(
                "Text extraction took {seconds} seconds",
                array(
                    'seconds' => round(microtime(true) - $start, 2)
                )
            );

            $this->setValue($solrFieldname, $v);
        }
    }

    /**
     * Helper method for setting a value in a Solr document.
     *
     * @param string $solrFieldname
     * @param mixed $value
     */
    protected function setValue($solrFieldname, $value)
    {
        $this->solrDocument->setField($solrFieldname, $value);
    }

    /**
     * @param string $solrFieldname
     * @param mixed $value
     */
    protected function setMultiValue($solrFieldname, $value)
    {
        $this->solrDocument->setMultiValue($solrFieldname, $value);
    }

    /**
     * Helper method for getting a value of a Propel object by guessing it's accessor method.
     *
     * @param string $propelFieldname
     * @param mixed|null $propelObject
     * @param array $parameters
     * @return mixed
     */
    protected function getObjectProperty($propelFieldname, $propelObject = null, array $parameters = array())
    {
        if ($propelObject === null) {
            $propelObject = $this->object;
            $peer = $this->peer;
        } else {
            $peer = $propelObject->getPeer();
        }

        try {
            $method = 'get' . $peer->translateFieldname(
                    $propelFieldname,
                    \BasePeer::TYPE_COLNAME,
                    \BasePeer::TYPE_PHPNAME
                );
        } catch (\PropelException $e) {
            // fallback:
            $method = 'get' . ucfirst($propelFieldname);
        }

        return call_user_func_array(array($propelObject, $method), $parameters);
    }

    /**
     * Set the boost factor for the Solr Document. The higher the $boost, the higher this document will be ranked.
     *
     * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false.
     */
    protected function boost($boost)
    {
        $this->solrDocument->setBoost($boost);
    }
}
