<?php
declare(strict_types=1);

namespace Strata\Data\Data;

use Strata\Data\DataInterface;
use Strata\Data\Pagination;
use Strata\Data\Permissions;
use Strata\Data\Traits\CheckPermissionsTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RestApi implements DataInterface
{
    use CheckPermissionsTrait;

    /** @var string */
    protected $baseUri;

    /** @var string */
    protected $endpoint;

    /**
     * Constructor
     * @param string $baseUri API base URI
     * @param Permissions $permissions (if not passed, default permission = read)
     */
    public function __construct(string $baseUri, Permissions $permissions = null)
    {
        $this->baseUri = $baseUri;

        if ($permissions === null) {
            $permissions = new Permissions();
        }
        $this->setPermissions($permissions);
    }

    /**
     * Return base URI
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Set the current endpoint to use with the data request
     * @param string $endpoint
     * @return RestApi
     */
    public function from(string $endpoint): RestApi
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Get endpoint URL / path
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Get a single content item
     *
     * API endpoint format is expected to be: base_url/content_type/id
     *
     * @param string $apiEndpoint API endpoint to query
     * @param mixed $id Identifier of item to return
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
     * @throws \Strata\Frontend\Exception\NotFoundException
     */
    public function getOne($id): array
    {
        $this->permissionRead();
        $this->expectedResponseCode(200);

        $response = $this->get(sprintf('%s/%s', $apiEndpoint, $id));
        $data = $this->parseJsonResponse($response);

        return $data;
    }

    public function getList(array $options)
    {
        // TODO: Implement getList() method.
    }

    public function hasResults(): bool
    {
        // TODO: Implement hasResults() method.
    }

    public function getPagination(int $page, int $limit, ResponseInterface $response): Pagination
    {
        // TODO: Implement getPagination() method.
    }
}
