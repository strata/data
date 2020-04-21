<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Exception\PermissionException;
use Strata\Data\Response\ListResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface DataInterface
{
    /**
     * Constructor
     * @param string $baseUri Base URI for data / API
     * @param Permissions $permissions (if not passed, default permission is read-only)
     */
    public function __construct(string $baseUri, Permissions $permissions = null);

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
     * Return base URI
     * @return string
     */
    public function getBaseUri(): string;

    /**
     * Get endpoint URL / path
     * @return string
     */
    public function getEndpoint(): string;

    /**
     * Set the current endpoint to use with the data request
     * @param string $endpoint
     * @return DataInterface
     */
    public function from(string $endpoint): DataInterface;

    public function getOne($identifier, array $uriParams, array $requestOptions = []): ResponseInterface;
    public function getList(Query $query, array $uriParams, array $requestOptions = []): ListResponse;

    public function hasResults(): bool;
    public function getPagination(int $page, int $limit, ResponseInterface $response) : Pagination;
}