<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Solr;

use Psr\Log\LoggerInterface;
use Webfactory\ContentMapping\DestinationAdapter;

/**
 * Apapter for an Apache Solr database as a destination system.
 *
 * @final by default
 */
final class SolrDestinationAdapter implements DestinationAdapter
{
    /**
     * @var \Apache_Solr_Service
     */
    private $solrService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Apache_Solr_Document[]
     */
    private $newOrUpdatedDocuments = array();

    /**
     * @var string[]|int[]
     */
    private $deletedDocumentIds = array();

    /**
     * @param \Apache_Solr_Service $solrService
     */
    public function __construct(\Apache_Solr_Service $solrService)
    {
        $this->solrService = $solrService;
    }

    public function setLogger(LoggerInterface $log)
    {
        $this->logger = $log;
    }

    /**
     * @param string $objectClass
     * @return \ArrayIterator
     * @throws \Apache_Solr_InvalidArgumentException
     */
    public function getObjectsOrderedById($objectClass)
    {
        $res = $this->solrService->search(
            '{!raw f=objectclass}' . $objectClass,
            0,
            1000000,
            array(
                'fl' => 'id,objectid,objectclass,hash',
                'sort' => 'objectid asc'
            )
        )->response;

        $this->logger->info(
            "SolrDestinationAdapter found {number} objects for objectClass {objectClass}",
            array(
                'number' => $res->numFound,
                'objectClass' => $objectClass,
            )
        );

        return new \ArrayIterator($res->docs);
    }

    /**
     * @param int $id
     * @param string $className
     * @return \Apache_Solr_Document
     */
    public function createObject($id, $className)
    {
        $document = new \Apache_Solr_Document();
        $document->setField('id', $className . ':' . $id);
        $document->setField('objectid', $id);
        $document->setField('objectclass', $className);

        return $document;
    }

    /**
     * @param \Apache_Solr_Document $destinationObject
     */
    public function delete($destinationObject)
    {
        $field = $destinationObject->getField('id');
        $this->deletedDocumentIds[] = $field['value'];
        $this->possiblyFlush();
    }

    /**
     * This method is a hook e.g. to notice an external change tracker that the $object has been updated.
     *
     * @param \Apache_Solr_Document $objectInDestinationSystem
     */
    public function updated($objectInDestinationSystem)
    {
        $this->newOrUpdatedDocuments[] = $objectInDestinationSystem;
        $this->possiblyFlush();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->flush();

        $this->logger->info("Solr commit and optimize");
        $this->solrService->commit(); // macht auch ein optimize()
        $this->logger->info("Finished commit");
    }

    /**
     * Get the id of an \Apache_Solr_Document object in the destination system.
     *
     * @param \Apache_Solr_Document $objectInDestinationSystem
     * @return int
     */
    public function idOf($objectInDestinationSystem)
    {
        $field = $objectInDestinationSystem->getField('objectid');
        return $field['value'];
    }

    protected function possiblyFlush()
    {
        if ((count($this->deletedDocumentIds) + count($this->newOrUpdatedDocuments)) >= 20) {
            $this->flush();
        }
    }

    protected function flush()
    {
        if ($this->deletedDocumentIds || $this->newOrUpdatedDocuments) {
            $this->logger->info(
                "Flushing {numberInsertsUpdates} inserts or updates and {numberDeletes} deletes",
                array(
                    'numberInsertsUpdates' => count($this->newOrUpdatedDocuments),
                    'numberDeletes' => count($this->deletedDocumentIds),
                )
            );
            if ($this->deletedDocumentIds) {
                $this->solrService->deleteByMultipleIds($this->deletedDocumentIds);
            }
            if ($this->newOrUpdatedDocuments) {
                $this->solrService->addDocuments($this->newOrUpdatedDocuments);
            }
            $this->deletedDocumentIds = array();
            $this->newOrUpdatedDocuments = array();
            $this->logger->debug("Flushed");
        }
    }
}
