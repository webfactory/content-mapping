<?php
namespace Webfactory\ContentMapping;

/**
 * When a DestinationAdapter implementation also implements this interface,
 * it will be notified after every step the Synchronizer made.
 */
interface ProgressListenerInterface
{
    /**
     * Callback method that will be called after every single object has been processed. That is,
     * - after createObject() / updated() calls for new objects
     * - after prepareUpdate() (for UpdateableObjectProviders) / updated() calls and changed objects
     * - after delete() calls for removed objects.
     *
     * @return void
     */
    public function afterObjectProcessed();
}
