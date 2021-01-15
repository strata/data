<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

use Strata\Data\Exception\InvalidFilterOrderException;
use Strata\Data\Filter\FilterInterface;

class FiltersTrait
{
    protected $filters = [];
    protected $nextOrder = 10;

    /**
     * Add a filter
     * @param FilterInterface $filter Filter to apply to data
     * @param int|null $order Order, lower numbers happen first. If not set increments in values of 10
     */
    public function addFilter(FilterInterface $filter, int $order = null)
    {
        if ($order === null) {
            $order = $this->nextOrder;
            $this->nextOrder = $this->nextOrder + 10;
        }

        if (in_array($order, array_keys($this->filters))) {
            throw new InvalidFilterOrderException(sprintf('You cannot set a filter order of %s, since this is already set', $order));
        }

        $this->filters[$order] = $filter;
    }

    /**
     * Return array of filters, sorted by weight
     * @return array
     */
    public function getFilters(): array
    {
        ksort($this->filters);
        return $this->filters;
    }

    /**
     * Run filters on the passed data string
     * @param string $data
     */
    public function filter(string $data)
    {
        /** @var FilterInterface $filter */
        foreach ($this->getFilters() as $filter) {
            $data = $filter->filter($data);
        }
        return $data;
    }

}
