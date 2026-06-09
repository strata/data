<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Pagination\Pagination;
use Strata\Data\Traits\IterableTrait;

/**
 * Class to manage a collection of items
 *
 * @package Strata\Data\Model
 */
class Collection implements CollectionInterface
{
    use IterableTrait;

    private Pagination $pagination;

    /**
     * Set collection array
     * @param array $collection
     */
    public function setCollection(array $collection)
    {
        $this->collection = [];
        foreach ($collection as $item) {
            $this->add($item);
        }
    }

    /**
     * Add an item to the collection
     * @param array|object $item
     */
    public function add(array|object $item)
    {
        $this->collection[] = $item;
    }

    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @return bool true on success or false on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->collection[$offset] ?? null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->collection[$offset]);
    }
}
