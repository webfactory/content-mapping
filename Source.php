<?php

namespace Webfactory\ContentMapping;

use Monolog\Logger;

/*
 * Eine Quelle für Objekte, die in ein anderes System abgebildet werden sollen.
 * Die ObjectClass ist ein (beliebiger) String, der den Content-Typ identifiziert.
 * Der Iterator muss ContentMappables liefern, deren IDs
 * jeweils eindeutig im Kontext einer ObjectClass sind.
 * Zielsysteme (ContentMappingDestination) können weitere Anforderungen an die Objekte
 * stellen, die der Iterator liefert (z. B. speziellere Interfaces).
 */
interface Source {

    public function getObjectClass();
    public function getObjectIterator();
    public function setLogger(Logger $log);

}
