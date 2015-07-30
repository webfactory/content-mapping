<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Doctrine;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Webfactory\ContentMapping\SourceAdapter;

/**
 * Implementation for Doctrine as a source system.
 *
 * @final by default.
 */
final class GenericDoctrineSourceAdapter implements SourceAdapter
{
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $repositoryMethod;

    /**
     * @param EntityRepository $repository to query
     * @param string $repositoryMethod Which returns a Collection of all objects to map, ordered by their ascending IDs.
     */
    public function __construct($repository, $repositoryMethod)
    {
        $this->repository = $repository;
        $this->repositoryMethod = $repositoryMethod;
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectsOrderedById()
    {
        $entities = $this->repository->{$this->repositoryMethod}();
        if (is_array($entities) === false) {
            throw new \RuntimeException(
                'The result of ' . get_class($this->repository) . '->' . $this->repositoryMethod . '() is no array, '
                . 'which it has to be if you wish to use ' . __CLASS__
            );
        }

        /** @var Collection mixed[] */
        return new \ArrayIterator($entities);
    }
}
