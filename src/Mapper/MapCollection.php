<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Collection;
use Strata\Data\CollectionInterface;
use Strata\Data\Exception\MapperException;
use Strata\Data\Exception\PaginationException;
use Strata\Data\Helper\UnionTypes;
use Strata\Data\Pagination\Pagination;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;

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
        UnionTypes::assert('$totalResults', $totalResults, 'string', 'int');
        $this->totalResults = $totalResults;
        return $this;
    }

    /**
     * @param string|int $resultsPerPage
     * @return $this Fluent interface
     */
    public function resultsPerPage($resultsPerPage): MapCollection
    {
        UnionTypes::assert('$resultsPerPage', $resultsPerPage, 'string', 'int');
        $this->resultsPerPage = $resultsPerPage;
        return $this;
    }

    /**
     * @param string|int $currentPage
     * @return $this Fluent interface
     */
    public function currentPage($currentPage): MapCollection
    {
        UnionTypes::assert('currentPage', $currentPage, 'string', 'int');
        $this->currentPage = $currentPage;
        return $this;
    }

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
                try {
                    $pagination->setTotalResults((int) $propertyAccessor->getValue($data, $totalResults));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $totalResults property %s', $totalResults), 0, $e);
                }
                break;
        }
        switch (gettype($resultsPerPage)) {
            case 'integer':
                $pagination->setResultsPerPage($resultsPerPage);
                break;
            case 'string':
                try {
                    $pagination->setResultsPerPage((int) $propertyAccessor->getValue($data, $resultsPerPage));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $resultsPerPage property %s', $totalResults), 0, $e);
                }
                break;
        }
        switch (gettype($currentPage)) {
            case 'integer':
                $pagination->setPage($currentPage);
                break;
            case 'string':
                try {
                    $pagination->setPage((int) $propertyAccessor->getValue($data, $currentPage));
                } catch (NoSuchIndexException $e) {
                    throw new MapperException(sprintf('Cannot read $currentPage property %s', $totalResults), 0, $e);
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
            $paginator = $this->paginationBuilder($this->paginationData, $this->totalResults, $this->resultsPerPage, $this->currentPage);
        } else {
            $paginator = $this->paginationBuilder($data, $this->totalResults, $this->resultsPerPage, $this->currentPage);
        }
        $collection->setPagination($paginator);

        return $collection;
    }
}
