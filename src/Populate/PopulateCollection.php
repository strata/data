<?php
declare(strict_types=1);

namespace Strata\Data\Populate;

use Strata\Data\Exception\PopulateException;
use Strata\Data\Model\Response;
use Strata\Data\Pagination\Pagination;

class PopulateCollection implements PopulateInterface
{
    use PropertyTrait;

    private Response $response;
    private ?string $property;
    private ?int $totalResults = null;
    private ?int $resultsPerPage = null;
    private ?int $currentPage = null;

    /**
     * Constructor
     * @param Response $response Response object to extract pagination data from
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Set the property name to populate data from
     *
     * @param $property
     * @return PopulateCollection Fluent interface
     */
    public function fromProperty($property): PopulateCollection
    {
        $this->property = $property;
        return $this;
    }


    public function totalResults(int $totalResults): PopulateCollection
    {
        $this->totalResults = $totalResults;
        return $this;
    }

    public function totalResultsFromMeta(string $field): PopulateCollection
    {
        $this->totalResults = $this->getMetadata($field, $this->response,PropertyTrait::TYPE_INT);
        return $this;
    }

    public function totalResultsFromProperty(string $field): PopulateCollection
    {
        $this->totalResults = $this->response->getRawProperty($field);
        return $this;
    }

    public function resultsPerPage(int $resultsPerPage): PopulateCollection
    {
        $this->resultsPerPage = $resultsPerPage;
        return $this;
    }

    public function resultsPerPageFromMeta(string $field): PopulateCollection
    {
        $this->resultsPerPage = $this->response->getMeta($field);
        return $this;
    }

    public function resultsPerPageFromProperty(string $field): PopulateCollection
    {
        $this->resultsPerPage = $this->response->getRawProperty($field);
        return $this;
    }

    public function currentPage(int $currentPage): PopulateCollection
    {
        $this->currentPage = $currentPage;
        return $this;
    }

    public function currentPageFromMeta(string $field): PopulateCollection
    {
        $this->currentPage = $this->response->getMeta($field);
        return $this;
    }

    public function currentPageFromProperty(string $field): PopulateCollection
    {
        $this->currentPage = $this->response->getRawProperty($field);
        return $this;
    }

    public function getPaginator(): Pagination
    {
        return new Pagination($this->totalResults, $this->resultsPerPage, $this->currentPage);
    }

    /**
     * Returns a populated response object
     *
     * Data is populated from raw data according to rules of the Populate object
     *
     * @param Response $response
     * @return Response
     */
    public function populate(Response $response): Response
    {
        if (empty($this->property)) {
            throw new PopulateException('Cannot populate data, property name not set');
        }

        $collection = $response->getRawProperty($this->property);
        if ($collection === null) {
            throw new PopulateException(sprintf('Cannot populate data, property name "%s" not found in raw data', $this->property));
        }

        foreach ($collection as $item) {
            $response->add($response->getRequestId(), $item);
        }

        $response->setPagination($this->getPaginator());

        return $response;
    }


}