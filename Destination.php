<?php

namespace Webfactory\ContentMapping;

use Iterator;
use Psr\Log\LoggerInterface;

/**
 * Ziel-System, das Content aufnehmen soll.
 */
interface Destination
{
    /**
     * Gibt einen Iterator über \Webfactory\ContentMapping\Mappable zurück. Jedes im Zielsystem vorhandene Objekt der
     * $objectClass wird durch genau eine Mappable-Instanz repräsentiert.
     *
     * @param string $objectClass
     * @return Iterator über \Webfactory\ContentMapping\Mappable
     */
    public function getMappablesOrderedById($objectClass);

    /**
     * Bilde ein neues $sourceObject der Klasse $objectClass aus dem Quell-System im Ziel-System ab.
     *
     * @param string $objectClass
     * @param Mappable $sourceObject
     * @throws \Exception für inkompatible $sourceObject
     */
    public function insert($objectClass, Mappable $sourceObject);

    /**
     * Aktualisiere das $destinationObject im Ziel-System anhand seines aktuelleren Quell-System-Pendants $sourceObject.
     *
     * @param Mappable $destinationObject
     * @param Mappable $sourceObject
     * @throws \Exception für inkompatible $sourceObject
     */
    public function update(Mappable $destinationObject, Mappable $sourceObject);

    /**
     * Lösche das $destinationObject im Ziel-System. Insb. aufgerufen, wenn es keine Entsprechung mehr im Quell-System
     * gibt.
     *
     * @param Mappable $destinationObject
     * @throws \Exception für inkompatible $sourceObject
     */
    public function delete(Mappable $destinationObject);

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);
}
