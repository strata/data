<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Collection\ListInterface;
use Strata\Data\Exception\PermissionException;

interface DataInterface
{
    /**
     * Check we have permission to create data
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionCreate($throw = true): bool;

    /**
     * Check we have permission to read data
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionRead($throw = true): bool;

    /**
     * Check we have permission to update data
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionUpdate($throw = true): bool;

    /**
     * Check we have permission to delete data
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionDelete($throw = true): bool;

    /**
     * Set the base URI to use for all requests
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri);

    /**
     * Return base URI of data to use for all requests
     * @return string
     */
    public function getBaseUri(): string;

    /**
     * Set the current endpoint to use with the data request
     * @param string $endpoint
     */
    public function setEndpoint(string $endpoint);

    /**
     * Get endpoint URL / path of current data request
     * @return string|null
     */
    public function getEndpoint(): ?string;

    /**
     * Return URI for current data request (base URI + endpoint)
     * @return string
     */
    public function getUri(): string;

    /**
     * Set the endpoint to use with the current data request
     * @param string $endpoint
     * @return DataInterface Fluent interface
     */
    // public function from(string $endpoint): DataInterface;

    /**
     * Set the query object to use with current data request
     * @param Query $query
     * @return DataInterface
     */
    // public function query(Query $query): DataInterface;

    /**
     * Return one item
     * @param $identifier Identifier to return item (e.g. ID, data item name)
     * @param array $requestOptions Array of options to pass to the request
     * @return Content for the item
     * @throws DataNotFoundException If data not found
     */
    public function getOne($identifier, array $requestOptions = []);

    /**
     * Return a list of items
     * @param Query $query Query object to generate the list
     * @param array $requestOptions Array of options to pass to the request
     * @return ListAbstract
     */
    public function getList(Query $query = null, array $requestOptions = []): ListInterface;

    /**
     * Whether the last response has any results
     * @return bool
     */
    public function hasResults(): bool;

    /* public function getPagination(int $page, int $limit, ListInterface $response) : Pagination; */
}