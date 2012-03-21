<?php

namespace Webfactory\ContentMapping\Propel;

use Webfactory\ContentMapping\Source as BaseSource;
use Monolog\Logger;

abstract class Source implements BaseSource {

    private $peer;
    protected $log;
    protected $extraction;

    protected function getPeer() {
        if (!$this->peer) $this->peer = $this->createPeer();
        return $this->peer;
    }

    public function setLogger(Logger $log) {
        $this->log = $log;
    }

    public function setExtraction(Extraction $extraction) {
        $this->extraction = $extraction;
    }

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
        return $this->createMapper($this->hydrateCurrent($rs));
    }

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
