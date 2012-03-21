<?php

namespace Webfactory\ContentMapping\Propel;

class GenericSource extends Source {

    protected $objectClass;
    protected $mapperClass;
    protected $resultSetMethod;


    public function __construct($objectClass, $mapperClass, $resultSetMethod = null) {
        $this->objectClass = $objectClass;
        $this->mapperClass = $mapperClass;
        $this->resultSetMethod = $resultSetMethod ? $resultSetMethod : 'doSelectRS';
    }

    public function getObjectClass() {
        return $this->objectClass;
    }

    protected function createPeer() {
        $cls = \Classloader::load("{$this->objectClass}Peer");
        return new $cls();
    }

    protected function createMapper($baseObject) {
        $cls = $this->mapperClass;
        return new $cls($baseObject, $this->log, $this->extraction);
    }

    protected function createResultSet($peer, \Criteria $crit) {
        $m = $this->resultSetMethod;
        return $peer->$m($crit);
    }

}
