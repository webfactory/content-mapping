<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Propel;

/**
 * Iterator over a result set that applies a callback on each item.
 */
final class ResultSetWithCallbackIterator implements \Iterator
{
    /**
     * @var \ResultSet Result set to iterate over.
     */
    private $resultSet;

    /**
     * @var mixed Result of the callback applied on the current element.
     */
    private $current;

    /**
     * @var callable Callback to apply on each of the elements.
     */
    private $callback;

    /**
     * @param \ResultSet $resultSet
     * @param $callback
     */
    public function __construct(\ResultSet $resultSet, $callback)
    {
        $this->resultSet = $resultSet;
        $this->callback = $callback;
    }

    /**
     * @return mixed|null Result of the callback applied on the current element.
     */
    public function current()
    {
        if ($this->resultSet && !$this->current) {
            $this->current = call_user_func_array($this->callback, array($this->resultSet));
        }

        return $this->current;
    }

    /**
     * @return null
     */
    public function key()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        if ($this->resultSet) {
            $this->current = null;
            $this->resultSet->first();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        if ($this->resultSet) {
            return !$this->resultSet->isAfterLast();
        }
    }

    /**
     * @return bool
     * @throws \SQLException
     */
    public function next()
    {
        if ($this->resultSet) {
            $this->current = null;
            return $this->resultSet->next();
        }
    }
}
