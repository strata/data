<?php

declare(strict_types=1);

namespace Strata\Data\Traits;

/**
 * Iterator functionality for use with collections which have string-based keys
 *
 * E.g.
 * $collection = ['name1' => item, 'name2' -> item]
 *
 * Can be used with classes that implement \Iterator, \Countable, \ArrayAccess
 * Colection data is stored in the $this->collection array
 *
 * @see https://www.php.net/iterator
 * @see https://www.php.net/countable
 * @see https://www.php.net/arrayaccess
 * @package Strata\Data\Traits
 */
trait IterableKeyBasedTrait {

    use IterableTrait;

    /**
     * Return array of keys for collection, to allow us to iterate over the collection
     * @return array
     */
    public function getArrayKeys(): array
    {
        return array_keys($this->collection);
    }

    /**
     * Return current item
     * @return mixed
     */
    public function current()
    {
        return $this->collection[$this->key()];
    }

    /**
     * Is the current position in the collection valid?
     * @return bool
     */
    public function valid()
    {
        return isset($this->getArrayKeys()[$this->position]);
    }

    /**
     * Seek to the passed position in the collection
     * @param $position
     * @throws OutOfBoundsException
     */
    public function seek($position)
    {
        if (!isset($this->getArrayKeys()[$position])) {
            throw new \OutOfBoundsException(sprintf('Invalid seek position: %s', $position));
        }
        $this->position = $position;
    }

    /**
     * Return key of current item in collection
     * @return string
     */
    public function key(): string
    {
        return $this->getArrayKeys()[$this->position];
    }

    /**
     * Whether an offset exists
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /**
     * Retrieve item from collection by offset
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->collection[$offset];
        }
    }

    /**
     * Add item to collection
     * @param $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->collection[$offset] = $value;
    }

    /**
     * Unset an item from the collection
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->collection[$offset]);
        }
    }

}