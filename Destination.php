<?php

namespace Webfactory\ContentMapping;

use Monolog\Logger;

/*
 * Ein Zielsystem, das Content aufnehmen soll.
 * Die insert/update/delete-Methoden dürfen Exceptions auslösen, wenn die (vom Quell-System
 * gelieferten) ContentMappable-Objekte inkompatibel sind, also zum Beispiel notwendige
 * zusätzliche Interfaces nicht implementieren.
 */
interface Destination {

    public function getObjectIterator($objectClass);
    public function insert($objectClass, Mappable $sourceObject);
    public function update(Mappable $destinationObject, Mappable $sourceObject);
    public function delete(Mappable $destinationObject);
    public function setLogger(Logger $log);

}
