<?php
declare(strict_types=1);

namespace Strata\Data\Collection;

use Strata\Data\Pagination\Pagination;
use Strata\Data\Pagination\PaginationInterface;
use Strata\Data\Exception\PaginationException;
use Strata\Data\Traits\IterableTrait;

/**
 * Simple class to model the response data for lists of data
 *
 * @package Strata\Frontend\Api
 */
abstract class ListAbstract implements ListInterface
{
    use IterableTrait;

    /**
     * Array of metadata
     *
     * This is for any data that is not part of the paginated results
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Pagination object (for lists)
     *
     * @var PaginationInterface
     */
    protected $pagination;

    /**
     * Constructor
     * @param array $data Array of data which makes up response
     * @param PaginationInterface|null $pagination Pagination object
     * @throws PaginationException
     */
    public function __construct(array $data, PaginationInterface $pagination = null)
    {
        $this->setCollection($data);

        if ($pagination === null) {
            $pagination = new Pagination();
            $total = $this->count();
            $pagination->setTotalResults($total)
                        ->setPage(1)
                        ->setTotalPages(1)
                        ->setResultsPerPage($total);
        }
        $this->setPagination($pagination);
    }

    /**
     * Set pagination object
     *
     * @param PaginationInterface $pagination
     * @return ListInterface
     */
    public function setPagination(PaginationInterface $pagination): ListInterface
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * Return pagination object
     *
     * @return PaginationInterface
     */
    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }

    /**
     * Set the metadata via an array
     *
     * @param array $metadata
     * @return ListInterface
     */
    public function setMetadata(array $metadata): ListInterface
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Add one metadata item
     *
     * @param string $name
     * @param $value
     * @return ListInterface
     */
    public function addMetadata(string $name, $value): ListInterface
    {
        $this->metadata[$name] = $value;
        return $this;
    }

    /**
     * Does the specified metadata exist?
     *
     * @param string $name
     * @return bool
     */
    public function hasMetadata(string $name): bool
    {
        return isset($this->metadata[$name]);
    }

    /**
     * Return one metadata item
     *
     * @param string $name
     * @return mixed|null
     */
    public function getMetadata(string $name)
    {
        if ($this->hasMetadata($name)) {
            return $this->metadata[$name];
        }
        return null;
    }

    /**
     * Return all metadata
     *
     * @return array
     */
    public function getAllMetadata(): array
    {
        return $this->metadata;
    }

}
