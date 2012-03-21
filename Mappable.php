<?php

namespace Webfactory\ContentMapping;
/*
 * Ein Objekt, das zwischen zwei Content-Systemen abgebildet werden soll.
 * Die Objekt-ID muss numerisch sein (und nur im Kontext gleichartiger Objekte eindeutig),
 * der Hash ist ein beliebiger String.
 */
interface Mappable {

    public function getObjectId();
    public function getHash();

}
