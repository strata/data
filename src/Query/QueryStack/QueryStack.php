<?php

declare(strict_types=1);

namespace Strata\Data\Query\QueryStack;

use Strata\Data\Traits\IterableKeyBasedTrait;

/**
 * Query stack collection
 */
class QueryStack implements \Iterator, \Countable, \ArrayAccess
{
    use IterableKeyBasedTrait;

    /**
     * Add an item onto the stack
     *
     * @param string $name
     * @param StackItem $item
     */
    public function add(string $name, StackItem $item)
    {
        $this->offsetSet($name, $item);
    }

    /**
     * Does an item exist with this name?
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * Return item by name
     * @param string $name
     * @return StackItem
     */
    public function get(string $name): StackItem
    {
        return $this->offsetGet($name);
    }

    /**
     * Return current item
     * @return mixed
     */
    public function current(): StackItem
    {
        return $this->collection[$this->key()];
    }
}