<?php

namespace Webfactory\ContentMapping\Propel;

use Psr\Log\LoggerInterface;
use Webfactory\ContentMapping\Source as BaseSource;
use Webfactory\PdfTextExtraction\Extraction;

abstract class Source implements BaseSource {

    private $peer;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var Extraction
     */
    protected $extraction;

    protected function getPeer() {
        require_once('creole/Creole.php');
        \Creole::registerDriver('*', 'creole.contrib.DebugConnection');
        if (!$this->peer) $this->peer = $this->createPeer();
        return $this->peer;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger) {
        $this->log = $logger;
    }

    public function setExtraction(Extraction $extraction) {
        $this->extraction = $extraction;
    }

    /**
     * @param mixed $peer
     * @return string
     */
    private function getFullyQualifiedPKName($peer) {
        foreach ($peer->getTableMap()->getColumns() as $column)
            if ($column->isPrimaryKey())
                return $column->getFullyQualifiedName();
    }

    protected function prepareResult() {
        $p = $this->getPeer();
        $criteria = new \Criteria();
        $criteria->setDistinct();
        $criteria->addAscendingOrderByColumn($this->getFullyQualifiedPKName($p));

        $this->log->debug("Propel\Source calling ...::createResultSet()");

        $r = $this->createResultSet($p, $criteria);

        $this->log->debug("...::createResultSet() returned");
        $this->log->debug("The last query was: " . \Propel::getConnection()->getLastExecutedQuery());

        return $r;
    }

    protected function hydrateCurrent(\MySQLResultSet $rs) {
        $cls = \Propel::import($this->getPeer()->getOMClass());
        $obj = new $cls();
        $obj->hydrate($rs);
        return $obj;
    }

    public function prepareMapper(\MySQLResultSet $rs) {
        $mapper = $this->createMapper($this->hydrateCurrent($rs));
        if ($this->extraction)
            $mapper->setExtraction($this->extraction);
        return $mapper;
    }

    /**
     * @return ResultSetIterator
     */
    public function getObjectIterator() {
        return new ResultSetIterator(
            $this->prepareResult(),
            array($this, 'prepareMapper')
        );
    }

    protected function createResultSet($peer, \Criteria $crit) {
        return $peer->doSelectRS($crit);
    }

    abstract protected function createPeer();
    abstract protected function createMapper($baseObject);
}
