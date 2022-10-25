<?php

namespace Webfactory\ContentMapping;

/**
 * Result of \Webfactory\ContentMapping\Mapper::map.
 *
 * @psalm-template Tw of ?object
 *
 * @psalm-immutable
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
     * @var ?object
     *
     * @psalm-var Tw
     */
    private $object;

    /**
     * Wether the object has been changed or not during the mapping.
     *
     * @var bool
     */
    private $objectHasChanged;

    /**
     * @var bool
     */
    private $objectIsUnmappable = false;

    /**
     * Convenience constructor to create a MapResult when the mapping
     * yields no changes and no update needs to be done.
     *
     * @psalm-return self<null>
     */
    public static function unchanged(): self
    {
        return new self(null, false);
    }

    /**
     * Convenience constructor to create a MapResult that carries a new or updated
     * object that needs to be written to the destination system.
     *
     * @param object $object The updated object
     *
     * @psalm-template Tx of object
     *
     * @psalm-param Tx $object
     *
     * @psalm-return self<Tx>
     */
    public static function changed(object $object): self
    {
        return new self($object, true);
    }

    /**
     * Constructor to indicate that the source object cannot be mapped (for whatever reason).
     *
     * Semantics are that it must not be inserted at the destination or be
     * removed if it already exists.
     *
     * NB. Such objects should not be provided by the SourceAdapter in the first place. However, there
     * may be circumstances where you cannot detect this unless you actually try the mapping.
     *
     * @psalm-return self<null>
     */
    public static function unmappable(): self
    {
        $return = new self(null, false);
        $return->objectIsUnmappable = true;

        return $return;
    }

    /**
     * @psalm-param Tw $object
     */
    public function __construct(?object $object, bool $objectHasChanged)
    {
        if ($objectHasChanged && null === $object) {
            throw new \InvalidArgumentException();
        }

        $this->object = $object;
        $this->objectHasChanged = $objectHasChanged;
    }

    /**
     * @see object
     *
     * @return ?object
     *
     * @psalm-return Tw
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @see objectHasChanged
     *
     * @psalm-assert-if-true !null $this->getObject()
     */
    public function getObjectHasChanged(): bool
    {
        return $this->objectHasChanged;
    }

    public function isUnmappableResult(): bool
    {
        return $this->objectIsUnmappable;
    }
}
