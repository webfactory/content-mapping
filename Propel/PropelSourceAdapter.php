<?php

namespace Webfactory\ContentMapping\Propel;

use Psr\Log\LoggerInterface;
use Webfactory\ContentMapping\SourceAdapter;

/**
 * Adapter for Propel as a source system for objects.
 */
abstract class PropelSourceAdapter implements SourceAdapter
{
    /**
     * @var mixed
     */
    private $peer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return \Iterator
     */
    public function getObjectsOrderedById()
    {
        /** @var $result \ResultSet */
        $result = $this->prepareResult();
        return new ResultSetWithCallbackIterator($result, array($this, 'hydrate'));
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \ResultSet
     * @throws \PropelException
     */
    protected function prepareResult()
    {
        $p = $this->getPeer();
        $criteria = new \Criteria();
        $criteria->setDistinct();
        $criteria->addAscendingOrderByColumn($this->getFullyQualifiedPKName($p));

        $this->logger->debug("Propel\Source calling ...::createResultSet()");

        $r = $this->createResultSet($p, $criteria);

        $this->logger->debug("...::createResultSet() returned");

        return $r;
    }

    /**
     * @return mixed
     */
    protected function getPeer()
    {
        if (!$this->peer) {
            require_once('creole/Creole.php');
            \Creole::registerDriver('*', 'creole.contrib.DebugConnection');
            $this->peer = $this->createPeer();
        }
        return $this->peer;
    }

    /**
     * @return mixed
     */
    abstract protected function createPeer();

    /**
     * @param mixed $peer
     * @return string
     */
    private function getFullyQualifiedPKName($peer)
    {
        foreach ($peer->getTableMap()->getColumns() as $column) {
            if ($column->isPrimaryKey()) {
                return $column->getFullyQualifiedName();
            }
        }
    }

    /**
     * @param mixed $peer
     * @param \Criteria $criteria
     * @return \ResultSet
     */
    abstract protected function createResultSet($peer, \Criteria $criteria);

    /**
     * Hydrate a result set. Used as a callback by the ResultSetWithCallbackIterator and hence public.
     *
     * @param \MySQLResultSet $resultSet
     * @return mixed Hydrated Propel object
     * @throws \PropelException
     */
    public function hydrate(\MySQLResultSet $resultSet)
    {
        $omClass = $this->getPeer()->getOMClass();
        $objectClass = \Propel::import($omClass);
        $object = new $objectClass();
        $object->hydrate($resultSet);
        return $object;
    }
}
