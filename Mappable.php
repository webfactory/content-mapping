<?php

namespace Webfactory\ContentMapping;

/**
 * Objekt, das zwischen einem Quell- und einem Ziel-System abgebildet werden soll.
 */
interface Mappable
{
    /**
     * @return string, muss numerisch und im Kontext der Objektklasse eindeutig sein.
     */
    public function getObjectId();

    /**
     * @return string, z.B. ein Datumsstring - wird nur auf Gleichheit überprüft
     */
    public function getHash();
}
