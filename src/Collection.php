<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Helper\UnionTypes;
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
    public function add($item)
    {
        UnionTypes::assert('$item', $item, 'array', 'object');
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
     * @param mixed $offset
     * @return bool true on success or false on failure.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset): mixed
    {
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
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
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->collection[$offset]);
    }
}
