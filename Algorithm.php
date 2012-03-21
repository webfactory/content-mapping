<?php

namespace Webfactory\ContentMapping;

use Monolog\Logger;

/*
 * Ein einfacher Algorithmus, um Content aus einer ContentMappingSource in eine ContentMappingDestination
 * abzubilden.
 */
class Algorithm {

    protected $source;
    protected $destination;
    protected $log;
    protected $forceUpdate;
    protected $sourceIterator, $destinationIterator;
    protected $objectClass;

    public function __construct(Source $source, Destination $destination, Logger $log, $forceUpdates = false) {
        $this->source = $source;
        $this->objectClass = $source->getObjectClass();
        $this->destination = $destination;
        $this->log = $log;
        $this->forceUpdate = $forceUpdates;

        $this->source->setLogger($log);
        $this->destination->setLogger($log);
    }

    protected function prepareIterators() {
        $this->log->info("Fetching ObjectIterator from source");
        $this->sourceIterator = $this->source->getObjectIterator();

        $this->log->info("Fetching ObjectIterator from destination");
        $this->destinationIterator = $this->destination->getObjectIterator($this->objectClass);

        $this->log->info("Finished fetching iterators");

        $this->destinationIterator->rewind();
        $this->sourceIterator->rewind();
    }

    protected function insert($sourceDocument) {
        $this->log->notice("New object #{$sourceDocument->getObjectId()} in source - starting insert.");
        $this->destination->insert($this->objectClass, $sourceDocument);
        $this->log->info("Finished insert.");
        $this->sourceIterator->next();
    }

    protected function delete($destinationDocument) {
        $this->log->notice("Object #{$destinationDocument->getObjectId()} no longer in source - starting delete.");
        $this->destination->delete($destinationDocument);
        $this->log->info("Finished delete.");
        $this->destinationIterator->next();
    }

    protected function update($sourceDocument, $destinationDocument) {
        $this->log->notice("Updating object #{$sourceDocument->getObjectId()}.");
        $this->destination->update($destinationDocument, $sourceDocument);
        $this->log->info("Finished update.");
        $this->destinationIterator->next();
        $this->sourceIterator->next();
    }

    public function run() {

        $this->prepareIterators();

        while ($this->destinationIterator->valid() && $this->sourceIterator->valid()) {
            $destinationDocument = $this->destinationIterator->current();
            $sourceDocument = $this->sourceIterator->current();
            $sid = $sourceDocument->getObjectId();
            $did = $destinationDocument->getObjectId();

            if ($did > $sid) {
                $this->insert($sourceDocument);
                continue;
            }

            if ($did < $sid) {
                $this->delete($destinationDocument);
                continue;
            }

            $sh = $sourceDocument->getHash();
            $dh = $destinationDocument->getHash();
            if ($this->forceUpdate || $sh != $dh) {

                if ($dh != $sh)
                    $this->log->notice("Hashes mismatch for object #{$did}: source = $sh, dest = $dh");

                $this->update($sourceDocument, $destinationDocument);
                continue;
            }

            # Kein Force, keine Änderung -> nix zu tun:
            $this->destinationIterator->next();
            $this->sourceIterator->next();
        }

        // Alle uebrig gebliebenen SourceDokumente einfuegen.
        if ($this->sourceIterator->valid()) {
            $this->log->notice("Inserting remaining source objects");

            while ($this->sourceIterator->valid())
                $this->insert($this->sourceIterator->current());
        }

        // Alle uebrig gebliebenen DestinationDokumente loeschen.
        if ($this->destinationIterator->valid()) {
            $this->log->notice("Deleting remaining destination objects");

            while ($this->destinationIterator->valid())
                $this->delete($this->destinationIterator->current());
        }

    }

}
