<?php
declare(strict_types=1);

namespace Strata\Data\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Strata\Frontend\Api\Permissions;
use Strata\Frontend\Response\ListResponse;
use Strata\Data\Pagination\Pagination;

interface ApiInterface
{
    public function setupHttpClient(): HttpClientInterface;
    public function setBaseUri(string $baseUri);
    public function getBaseUri(): string;
    public function request(string $method, string $uri, array $options): ResponseInterface;
    public function get(string $uri, array $queryParams = [], array $options = []): ResponseInterface;
    public function post(string $uri, array $postData = [], array $options): ResponseInterface;
    public function head(string $uri, array $options): ResponseInterface;
    public function setClient(HttpClientInterface $client);
    public function getClient(): HttpClientInterface;
    public function getUserAgent(): string;
    public function expectedResponseCode(int $code);
    public function throwOnFailedRequest($throw = true);
    public function permissionRead();
    public function permissionWrite();
    public function permissionDelete();
    public function getOne(string $uri, $id): array;
    public function list(string $uri, int $page = 1, array $options = []): ListResponse;
    public function getPagination(int $page, int $limit, ResponseInterface $response): Pagination;
}
