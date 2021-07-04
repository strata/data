<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Collection;
use Strata\Data\CollectionInterface;
use Strata\Data\Exception\MapperException;
use Strata\Data\Exception\PaginationException;
use Strata\Data\Helper\UnionTypes;
use Strata\Data\Pagination\Pagination;
use Strata\Data\Traits\PaginationPropertyTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;

class MapCollection extends MapperAbstract implements MapperInterface
{
    use PaginationPropertyTrait;

    private $paginationData = null;

    /**
     * Set data to extract pagination information from
     * @param $data
     * @return $this
     */
    public function fromPaginationData($data): MapCollection
    {
        UnionTypes::assert('$data', $data, 'array', 'object');
        $this->paginationData = $data;
        return $this;
    }

    /**
     * Generate pagination from an array or passed data
     *
     * @param array $data Array of data to get pagination information from
     * @return Pagination
     * @throws MapPaginationException If cannot read data properties to create Pagination
     * @throws PaginationException If cannot setup Pagination object successfully
     */
    public function paginationBuilder(array $data): Pagination
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $pagination = new Pagination();

        switch (gettype($this->getTotalResults())) {
            case 'integer':
                $pagination->setTotalResults($this->getTotalResults());
                break;
            case 'string':
                try {
                    $pagination->setTotalResults((int) $propertyAccessor->getValue($data, $this->getTotalResults()));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $totalResults property %s', $this->getTotalResults()), 0, $e);
                }
                break;
        }
        switch (gettype($this->resultsPerPage)) {
            case 'integer':
                $pagination->setResultsPerPage($this->getResultsPerPage());
                break;
            case 'string':
                try {
                    $pagination->setResultsPerPage((int) $propertyAccessor->getValue($data, $this->getResultsPerPage()));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $resultsPerPage property %s', $this->getResultsPerPage()), 0, $e);
                }
                break;
        }
        switch (gettype($this->getCurrentPage())) {
            case 'integer':
                $pagination->setPage($this->getCurrentPage());
                break;
            case 'string':
                try {
                    $pagination->setPage((int) $propertyAccessor->getValue($data, $this->getCurrentPage()));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $currentPage property %s', $this->getCurrentPage()), 0, $e);
                }
                break;
        }

        return $pagination;
    }

    /**
     * Map data to an item
     *
     * By default this returns an array, or pass a classname to return as an object
     *
     * @param array $data Data to map from
     * @param string|null $rootProperty Root property to map data from
     * @return CollectionInterface Mapped collection of arrays or objects
     * @throws MapperException
     */
    public function map(array $data, ?string $rootProperty = null): CollectionInterface
    {
        $collectionData = $this->getRootData($data, $rootProperty);

        // No data returned
        if ($data === null) {
            $collection = new Collection();
            $collection->setPagination(new Pagination(0));
            return $collection;
        }

        // We expect $data to be iterable so we can build a collection
        if (!is_iterable($collectionData)) {
            if (null !== $rootProperty) {
                throw new MapperException(sprintf('Cannot build a collection from $data at root element %s, not iterable', $rootProperty));
            }
            throw new MapperException('Cannot build a collection from $data, not iterable');
        }

        $className = $this->getCollectionClass();
        $collection = new $className();
        foreach ($collectionData as $item) {
            $collection->add($this->buildItemFromData($item));
        }

        if (null !== $this->paginationData) {
            $paginator = $this->paginationBuilder($this->paginationData);
        } else {
            $paginator = $this->paginationBuilder($data);
        }
        $collection->setPagination($paginator);

        return $collection;
    }
}
