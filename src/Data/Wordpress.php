<?php
declare(strict_types=1);

namespace Strata\Data\Data;

use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Exception\UriPatternException;
use Strata\Data\Query;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Strata\Data\DataInterface;
use Strata\Data\Traits\CheckPermissionsTrait;
use Strata\Data\Api\RestApiAbstract;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Strata\Data\Response\ListAbstract;
use Strata\Data\Pagination\Pagination;
use Strata\Data\Exception\ApiException;

use Strata\Frontend\Utils\FileInfoFormatter;

class Wordpress extends RestApiAbstract implements DataInterface
{
    // Default
    protected $endpoint = 'posts';

    /**
     * Setup HTTP client
     *
     * @return Client
     * @throws ApiException
     */
    public function setupHttpClient(): HttpClientInterface
    {
        return HttpClient::create([
            'base_uri' => $this->getBaseUri(),
            'headers' => [
                'User-Agent' => $this->getUserAgent(),
            ]
        ]);
    }

    public function getOne($identifier, array $uriParams, array $requestOptions = []): ResponseInterface
    {
        $this->permissionRead();
        $this->expectedResponseCode(200);

        return $this->get($this->getUri('one', $this->getEndpoint(), $identifier), $uriParams, $requestOptions);
    }


    public function getList(Query $query, array $uriParams, array $requestOptions = []): ListAbstract
    {
        $this->permissionRead();
        $this->expectedResponseCode(200);

        $uriParams = $query->getUriParams([
            'page'      => 'page',
            'per_page'  => 'perPage',
            'search'    => 'search',
            'order'     => 'order',
            'order_by'  => 'orderBy',
            'categories' => 'filter.categories',
            'tags'      => 'filter.tags'
        ]);

        if ($query->has) {
            return $this->get($this->getUri('list', $query->getEndpoint(), $uriParams), $requestOptions);
        }
    }
}
