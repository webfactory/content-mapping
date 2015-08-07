<?php

namespace Webfactory\ContentMapping;

/**
 * Result of \Webfactory\ContentMapping\Mapper::map.
 *
 * @final by default.
 */
final class MapResult
{
    /**
     * An object (not necessarily the same as the input $destinationObject of \Webfactory\ContentMapping\Mapper::map)
     * that was initialized with the values of the $destinationObject and then received the content of the
     * $sourceObject.
     *
     * E.g. a solarium client as a DestinationAdapter gives you readonly Documents as $destinationObject. In this case,
     * the mapper cannot write on the $destinationObject itself, so $object will be a different (writable) object than
     * $destinationObject but with the same values. A Doctrine client as the DestinationAdapter gives you writable
     * entities managed by the EntityManager, so $object should be the very same object as $destinationObject.
     *
     * Can be null if the $objectHasChanged is false.
     *
     * @var mixed|null $destinationObject
     */
    private $object;

    /**
     * Wether the object has been changed or not during the mapping.
     *
     * @var boolean
     */
    private $objectHasChanged;

    /**
     * @param mixed|null $object
     * @param bool  $objectHasChanged
     */
    public function __construct($object, $objectHasChanged)
    {
        $this->object = $object;
        $this->objectHasChanged = $objectHasChanged;
    }

    /**
     * @see object
     * @return mixed|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @see objectHasChanged
     * @return boolean
     */
    public function getObjectHasChanged()
    {
        return $this->objectHasChanged;
    }
}
