<?php

declare(strict_types=1);

namespace Strata\Data\Query;

/**
 * Help build API queries
 *
 * $query = new Query();
 * $query->setParams(['limit' => 10, 'section' => 'news']);
 * $query->setFields(['name', 'uri', 'date_published']);
 *
 * $item = $query->getItem();
 * or:
 * $collection = $query->getCollection();
 */
class Query
{
    private string $name;
    private bool $subRequest = false;
    private string $uri;
    private bool $run = false;
    private array $params = [];
    private array $fields = [];

    /**
     * Constructor
     * @param array $params Array of parameters to apply to this query
     * @param array $fields Array of fields to return for this query
     */
    public function __construct(array $params = [], array $fields = [])
    {
        if (!empty($params)) {
            $this->setParams($params);
        }
        if (!empty($fields)) {
            $this->setFields($fields);
        }
    }

    /**
     * Set array of parameters to apply to this query
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Add parameter
     *
     * @param string $key
     * @param mixed $value
     */
    public function addParam(string $key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * Set array of fields to return for this query
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Whether any fields are set for this query
     * @return bool
     */
    public function hasFields(): bool
    {
        return (!empty($this->fields));
    }

    /**
     * Return array of fields to return for this query
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Return array of parameters to apply to this query
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Return the query name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the query name
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Whether this request is a sub-request
     * @return bool
     */
    public function isSubRequest(): bool
    {
        return $this->subRequest;
    }

    /**
     * Set whether this request is a sub-request
     *
     * This surpresses HTTP errors for sub-requests
     *
     * @param bool $subRequest
     */
    public function setSubRequest(bool $subRequest): void
    {
        $this->subRequest = $subRequest;
    }

    /**
     * Return the URI for this query
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set the URI for this query
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * Whether this query has already run
     * @return bool
     */
    public function hasRun(): bool
    {
        return $this->run;
    }

    /**
     * Set whether the query has run
     * @param bool $run
     */
    public function setRun(bool $run): void
    {
        $this->run = $run;
    }

}