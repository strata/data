<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Collection;
use Strata\Data\Exception\MapperException;
use Strata\Data\Exception\PaginationException;
use Strata\Data\Helper\UnionTypes;
use Strata\Data\Pagination\Pagination;

class MapCollection extends MapperAbstract implements MapperInterface
{
    private $totalResults;
    private $resultsPerPage;
    private $currentPage = 1;
    private $paginationData = null;

    /**
     * Set total results
     *
     * @param string|int $totalResults
     * @return $this Fluent interface
     */
    public function totalResults($totalResults): MapCollection
    {
        if (!UnionTypes::stringOrInt($totalResults)) {
            throw new \InvalidArgumentException('$totalResults must be a string or integer');
        }
        $this->totalResults = $totalResults;
        return $this;
    }

    /**
     * @param string|int $resultsPerPage
     * @return $this Fluent interface
     */
    public function resultsPerPage($resultsPerPage): MapCollection
    {
        if (!UnionTypes::stringOrInt($resultsPerPage)) {
            throw new \InvalidArgumentException('$resultsPerPage must be a string or integer');
        }
        $this->resultsPerPage = $resultsPerPage;
        return $this;
    }

    /**
     * @param string|int $currentPage
     * @return $this Fluent interface
     */
    public function currentPage($currentPage): MapCollection
    {
        if (!UnionTypes::stringOrInt($currentPage)) {
            throw new \InvalidArgumentException('$currentPage must be a string or integer');
        }
        $this->currentPage = $currentPage;
        return $this;
    }

    public function fromPaginationData($data): MapCollection
    {
        if (!UnionTypes::arrayOrObject($data)) {
            throw new \InvalidArgumentException('$data must be an array or object');
        }
        $this->paginationData = $data;
        return $this;
    }

    /**
     * Generate pagination from an array or passed data
     *
     * @param array $data Array of data to get pagination information from
     * @param string|int|null $totalResults If string array property, or if int the value
     * @param string|int|null $resultsPerPage If string array property, or if int the value
     * @param string|int|null $currentPage If string array property, or if int the value
     * @return Pagination
     * @throws MapPaginationException If cannot read data properties to create Pagination
     * @throws PaginationException If cannot setup Pagination object successfully
     */
    public function paginationBuilder(array $data, $totalResults = null, $resultsPerPage = null, $currentPage = null): Pagination
    {
        $propertyAccessor = $this->getPropertyAccessor();
        $pagination = new Pagination();

        switch (gettype($totalResults)) {
            case 'integer':
                $pagination->setTotalResults($totalResults);
                break;
            case 'string':
                if (!$propertyAccessor->isReadable($data, $totalResults)) {
                    throw new MapperException(sprintf('Cannot read $totalResults property %s', $totalResults));
                }
                $pagination->setTotalResults($propertyAccessor->getValue($data, $totalResults));
                break;
        }
        switch (gettype($resultsPerPage)) {
            case 'integer':
                $pagination->setResultsPerPage($resultsPerPage);
                break;
            case 'string':
                if (!$propertyAccessor->isReadable($data, $resultsPerPage)) {
                    throw new MapperException(sprintf('Cannot read $resultsPerPage property %s', $resultsPerPage));
                }
                $pagination->setResultsPerPage($propertyAccessor->getValue($data, $resultsPerPage));
                break;
        }
        switch (gettype($currentPage)) {
            case 'integer':
                $pagination->setPage($currentPage);
                break;
            case 'string':
                if (!$propertyAccessor->isReadable($data, $currentPage)) {
                    throw new MapperException(sprintf('Cannot read $currentPage property %s', $currentPage));
                }
                $pagination->setPage($propertyAccessor->getValue($data, $currentPage));
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
     * @return Collection Mapped collection of arrays or objects
     * @throws MapperException
     */
    public function map(array $data, ?string $rootProperty = null): Collection
    {
        $collectionData = $this->getRootData($data, $rootProperty);

        // We expect $data to be iterable so we can build a collection
        if (!is_iterable($collectionData)) {
            if (null !== $rootProperty) {
                throw new MapperException(sprintf('Cannot build a collection from $data at root element %s, not iterable', $rootProperty));
            }
            throw new MapperException('Cannot build a collection from $data, not iterable');
        }

        $collection = new Collection();
        foreach ($collectionData as $item) {
            $collection->add($this->buildItemFromData($item));
        }

        if (null !== $this->paginationData) {
            $paginator = $this->paginationBuilder($this->paginationData, $this->totalResults, $this->resultsPerPage, $this->currentPage);
        } else {
            $paginator = $this->paginationBuilder($data, $this->totalResults, $this->resultsPerPage, $this->currentPage);
        }
        $collection->setPagination($paginator);

        return $collection;
    }
}
