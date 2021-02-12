<?php
declare(strict_types=1);

namespace Strata\Data\Model;

use Strata\Data\Pagination\Pagination;
use Strata\Data\Traits\IterableTrait;

/**
 * Cacheable collection of items returned from API request
 *
 * Usage:
 * use Strata\Data\Decode\DecoderStrategy;
 * use Strata\Data\Decode\Json;
 * use Strata\Data\Pagination\Pagination;
 *
 * $decoder = new DecoderStrategy(new Json());
 * $collection = new Collection();
 * $collection->add(new Item($uri, $data, $decoder));
 * $collection->add(new Item($uri2, $data2, $decoder));
 * $collection->setPagination(50, 2, 1);
 *
 * @package Strata\Data\Data
 */
class Collection implements \SeekableIterator, \Countable
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
     * @param Item $item
     */
    public function add(Item $item)
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
     * Return current item
     * @return mixed
     */
    public function current(): Item
    {
        return $this->collection[$this->position];
    }
}
