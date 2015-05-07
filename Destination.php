<?php

namespace Webfactory\ContentMapping;

use Iterator;
use Monolog\Logger;

/**
 * Ziel-System, das Content aufnehmen soll.
 */
interface Destination
{
    /**
     * Hole einen Iterator, der \Webfactory\ContentMapping\Mappable für die im Ziel-System existierenden Objekte
     * liefert, und zwar nach ihrer ID aufsteigend sortiert.
     *
     * @param string $objectClass
     * @return Iterator
     */
    public function getObjectIterator($objectClass);

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
     * @param Logger $log
     */
    public function setLogger(Logger $log);
}
