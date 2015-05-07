<?php

namespace Webfactory\ContentMapping\Propel;

class ResultSetCallbackIterator implements \Iterator
{
    /**
     * @var \MySQLResultSet
     */
    protected $resultSet;

    /**
     * @var mixed
     */
    protected $current;

    protected $callback;

    public function __construct(\MySQLResultSet $resultSet, $callback) {
        $this->resultSet = $resultSet;
        $this->callback = $callback;
    }

    public function current() {
        if ($this->resultSet && !$this->current)
            $this->current = call_user_func_array($this->callback, array($this->resultSet));

            return $this->current;
    }

    public function key() {
        return null;
    }

    public function rewind() {
        if ($this->resultSet) {
            $this->current = null;
            $this->resultSet->first();
        }
    }

    public function valid() {
        if ($this->resultSet) {
            return !$this->resultSet->isAfterLast();
        }
    }

    public function next() {
        if ($this->resultSet) {
            $this->current = null;
            return $this->resultSet->next();
        }
    }
}
