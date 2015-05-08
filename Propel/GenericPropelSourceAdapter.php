<?php

namespace Webfactory\ContentMapping\Propel;

/**
 * Automagically implementation of the adapter for Propel as a source system.
 *
 * @final by default
 */
final class GenericPropelSourceAdapter extends PropelSourceAdapter
{
    /**
     * @var string Name of the class of the objects provided by this source.
     */
    protected $className;

    /**
     * @var string Name of the method to call on the peer to get the objects ordered by their id.
     */
    protected $resultSetMethod;

    /**
     * @param string $className Name of the class of the objects provided by this source.
     * @param string $resultSetMethod Name of the method to call on the peer to get the objects ordered by their id.
     */
    public function __construct($className, $resultSetMethod = 'doSelectRS')
    {
        $this->className = $className;
        $this->resultSetMethod = $resultSetMethod;
    }

    /**
     * @return mixed
     */
    protected function createPeer()
    {
        $cls = \Classloader::load("{$this->className}Peer");
        return new $cls();
    }

    /**
     * {@inheritDoc}
     */
    protected function createResultSet($peer, \Criteria $criteria)
    {
        return $peer->{$this->resultSetMethod}($criteria);
    }
}
