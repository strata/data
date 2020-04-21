<?php
declare(strict_types=1);

namespace Strata\Data;

/**
 * Class to model an API / data query
 *
 * A Query must have:
 * - endpoint
 * - page
 *
 * All other properties are optional
 *
 * @package Strata\Data
 */
class Query
{
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';

    const COMBINE_COMMA = ',';
    const COMBINE_PLUS  = '+';
    const COMBINE_ARRAY = '[]';

    /**
     * Default strategy to combine multiple URI params
     * @var string
     */
    protected $defaultCombineStrategy = self::COMBINE_COMMA;

    /** @var string */
    protected $endpoint;

    /** @var int */
    protected $page = 1;

    /** @var int */
    protected $perPage;

    /** @var string */
    protected $limit;

    /** @var string */
    protected $search;

    /** @var string */
    protected $order;

    /** @var string */
    protected $orderBy;

    /** @var array */
    protected $filters = [];

    /**
     * Constructor
     * @param null $endpoint Optionally set the endpoint when instantiating this class
     */
    public function _construct($endpoint = null)
    {
        if ($endpoint !== null) {
            $this->endpoint($endpoint);
        }
    }

    /**
     * Set endpoint to query
     * @param string $endpoint
     * @return $this
     */
    public function endpoint(string $endpoint): Query
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Set page number to return
     * @param int $page
     * @return $this
     */
    public function page(int $page): Query
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Set number of pages to return per page (default 20)
     * @param int $perPage
     * @return $this
     */
    public function perPage(int $perPage): Query
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * Set a limit on the total number of results
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): Query
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set search keywords to filter results by
     * @param string $searchKeywords
     * @return $this
     */
    public function search(string $searchKeywords): Query
    {
        $this->search = $searchKeywords;
        return $this;
    }

    /**
     * Set whether to order in ascending (asc) or descending order (desc)
     * @param string $order
     * @return $this
     */
    public function order(string $order): Query
    {
        if ($order === self::ORDER_ASC || $order === self::ORDER_DESC) {
            $this->order = $order;
        }
        return $this;
    }

    /**
     * Order by a field
     *
     * @param string $orderBy
     * @return $this
     */
    public function orderBy(string $orderBy): Query
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Add a filter to the query
     *
     * @param string $name
     * @param $value
     * @return $this
     */
    public function addFilter(string $name, $value): Query
    {
        $this->filters[$name] = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return bool
     */
    public function hasPerPage(): bool
    {
        return ($this->perPage !== null);
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return bool
     */
    public function hasLimit(): bool
    {
        return ($this->limit !== null);
    }

    /**
     * @return string
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return ($this->search !== null);
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search;
    }

    /**
     * @return bool
     */
    public function hasOrder(): bool
    {
        return ($this->order !== null);
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @return bool
     */
    public function hasOrderBy(): bool
    {
        return ($this->orderBy !== null);
    }

    /**
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * @return bool
     */
    public function hasFilters(): bool
    {
        return (count($this->filters) > 0);
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function combine(array $data, int $strategy): array
    {

    }

    /**
     * Return array of URI params populated with query values
     *
     * This is made up of array key to return => Query param.
     * You can use filter.key to return any custom filters setup in the Query.
     *
     * E.g.
     * $uriParams = $query->getUriParams([
     *   'page'      => 'page',
     *   'per_page'  => 'perPage',
     *   'search'    => 'search',
     *   'order'     => 'order',
     *   'order_by'  => 'orderBy',
     *   'categories' => 'filter.categories',
     *   'tags'      => 'filter.tags'
     * ]);
     *
     * @param array $mapping
     * @param string $strategy Strategy to combine multiple values
     * @return array
     */
    public function getUriParams(array $mapping, string $strategy = null): array
    {
        if ($strategy === null) {
            $strategy = $this->defaultCombineStrategy;
        }

        $value = TODO;

        $params = [];
        foreach ($mapping as $uriParam => $queryParam) {
            if (empty($queryParam)) {
                continue;
            }
            if (is_array($queryParam)) {
                switch ($strategy) {
                    case self::COMBINE_COMMA:
                        $params[$uriParam] = implode(',', $queryParam);
                        break;
                    case self::COMBINE_PLUS:
                        $params[$uriParam] = implode('+', $queryParam);
                        break;
                    case self::COMBINE_ARRAY:
                        foreach ($queryParam as $value) {
                            $key = $uriParam . '[]';
                            $params[$key] = $value;
                        }
                        break;
                }
            } else {
                $params[$uriParam] = $queryParam;
            }
        }
        return $params;
    }

}