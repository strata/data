<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

/**
 * Iterator functionality
 *
 * Can be used with classes that implement \SeekableIterator, \Countable
 * Colection data is stored in the $this->collection array
 *
 * @see https://www.php.net/seekableiterator
 * @see https://www.php.net/countable
 * @package Strata\Data\Traits
 */
trait IterableTrait
{
    /**
     * Array of collection data
     * @var array
     */
    protected $collection = [];

    /**
     * Current position in collection
     * @var int
     */
    protected $position = 0;

    /**
     * Set collection array
     * @param array $collection
     */
    public function setCollection(array $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Return collection array
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * Return current item
     * @return mixed
     */
    public function current()
    {
        return $this->collection[$this->position];
    }

    /**
     * Return current position in collection
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Increment position in collection by one
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Rewind to start of collection
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Is the current position in the collection valid?
     * @return bool
     */
    public function valid()
    {
        return isset($this->collection[$this->position]);
    }

    /**
     * Seek to the passed position in the collection
     * @param $position
     * @throws OutOfBoundsException
     */
    public function seek($position)
    {
        if (!isset($this->collection[$position])) {
            throw new \OutOfBoundsException(sprintf('Invalid seek position: %s', $position));
        }
        $this->position = $position;
    }

    public function count() : int
    {
        return count($this->collection);
    }

}