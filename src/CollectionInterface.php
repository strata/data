<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Pagination\Pagination;

/**
 * Interface that represents a collection of items
 */
interface CollectionInterface extends \SeekableIterator, \Countable, \ArrayAccess
{
    public function setCollection(array $collection);

    public function add(array|object $item);

    public function setPagination(Pagination $pagination);

    public function getPagination(): Pagination;
}
