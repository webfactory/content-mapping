<?php

namespace Webfactory\ContentMapping;

use \Iterator;
use Psr\Log\LoggerInterface;

/**
 * Quell-System (z.B. Anbindung an eine Datenbank) für Objekte, die in ein Ziel-System (z.B. einen Solr-Index)
 * abgebildet werden sollen.
 */
interface Source
{
    /**
     * Hole String, der den Content-Typ identifiziert.
     *
     * @return string
     */
    public function getObjectClass();

    /**
     * Gibt einen Iterator über \Webfactory\ContentMapping\Mappable zurück. Jedes im Quellsystem vorhandene Objekt wird
     * durch genau eine Mappable-Instanz repräsentiert.
     *
     * @return Iterator über \Webfactory\ContentMapping\Mappable
     */
    public function getMappablesOrderedById();

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);
}
