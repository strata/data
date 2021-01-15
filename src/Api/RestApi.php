<?php
declare(strict_types=1);

namespace Strata\Data\Api;

use Strata\Data\HttpAbstract;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Strata\Frontend\Response\ListResponse;
use Strata\Data\Pagination\Pagination;

class RestApi extends HttpAbstract implements ApiInterface
{

    /**
     * Setup HTTP client
     * @return HttpClientInterface
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

    /**
     * Return a collection of content items
     *
     * @param string $apiEndpoint API endpoint to query for posts
     * @param int $page Page number to return
     * @param array $options Options to use when querying data from WordPress
     * @return ListResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
     * @throws \Strata\Frontend\Exception\PaginationException
     */
    public function list(string $apiEndpoint, $page = 1, array $options = []): ListResponse
    {
        $this->permissionRead();
        $this->expectedResponseCode(200);

        // @todo May need to create patterns for REST APIs for things like pagination, returning meta data. This is fixed for now.

        // Build query params
        $query = array_merge(['page' => $page], $options);

        $response = $this->get($apiEndpoint, ['query' => $query]);
        $data = $this->parseJsonResponse($response);

        if (isset($options['limit'])) {
            $limit = $options['limit'];
        } else {
            $limit = 10;
        }
        $pages = $this->getPagination($page, $limit, $response);

        $response = new ListResponse($data['results'], $pages);
        $response->setMetaData($data['meta']);
        return $response;
    }


    /**
     * Return pagination object for current request
     *
     * We expect pagination metadata to be stored in:
     *
     *  "meta": {
     *      "total_results": 148,
     *      "limit": 10,
     *      "page": 1
     *  },
     *
     * @todo Is limit the right word here? Consider per_page
     *
     * @param int $page Current page number
     * @param int $limit Number of results per page
     * @param ResponseInterface $response
     * @return Pagination
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     */
    public function getPagination(int $page, int $limit, ResponseInterface $response): Pagination
    {
        $pages = new Pagination();

        // @todo remove this duplication
        $data = $this->parseJsonResponse($response);

        if (isset($data['meta']) && isset($data['meta']['total_results'])) {
            $pages->setTotalResults((int) $data['meta']['total_results'])
                ->setResultsPerPage($limit)
                ->setPage($page);
        }

        return $pages;
    }
}
