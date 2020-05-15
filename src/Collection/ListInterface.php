<?php
declare(strict_types=1);

namespace Strata\Data\Collection;

use Strata\Data\Pagination\PaginationInterface;

interface ListInterface extends \SeekableIterator, \Countable
{
    /**
     * Set pagination object
     * @param PaginationInterface $pagination
     * @return ListInterface Fluent interface
     */
    public function setPagination(PaginationInterface $pagination): ListInterface;

    /**
     * Return pagination object
     * @return PaginationInterface
     */
    public function getPagination(): PaginationInterface;

    /**
     * Set array of metadata
     * @param array $metadata
     * @return ListInterface
     */
    public function setMetadata(array $metadata): ListInterface;

    /**
     * Add one metadata item
     * @param string $key
     * @param $value
     * @return ListInterface
     */
    public function addMetadata(string $key, $value): ListInterface;

    /**
     * Does the specified metadata exist?
     *
     * @param string $name
     * @return bool
     */
    public function hasMetadata(string $name): bool;

    /**
     * Return a single metadata value
     * @param string $name Name of metadata to return
     * @return mixed
     */
    public function getMetadata(string $name);

    /**
     * Return array of all metadata
     * @return array
     */
    public function getAllMetadata(): array;

    /**
     * Add one item to the list collection
     * @param $item
     * @return ListInterface
     */
    public function addItem($item): ListInterface;

    /**
     * Set collection array
     * @param array $collection
     */
    public function setCollection(array $collection);

    /**
     * Return collection array
     * @return array
     */
    public function getCollection(): array;

}
