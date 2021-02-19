<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\CachingHttpClient as SymfonyCachingHttpClient;
use Toflar\Psr6HttpCacheStore\Psr6Store;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class CachingHttpClient implements HttpClientInterface
{
    private $client;

    /**
     * Decorator to cache API responses via PSR-6 compatible Symfony HttpCache Store
     *
     * Supports auto-pruning of cache, invalidating cache via tags (via ContentCache-Tags header).
     *
     * ContentCache lifetime is determined by ContentCache-Control or Expires headers from API response, or default_ttl if not set.
     *
     * Usage:
     * $client = HttpClient::create();
     * $client = new CachingHttpClient($client, '/path/to/cache/storage', ['default_ttl' => 1800]);
     *
     * @see https://github.com/Toflar/psr6-symfony-http-cache-store
     * @param HttpClientInterface $client
     * @param string $cacheDirectory
     * @param array $defaultOptions
     */
    public function __construct(HttpClientInterface $client, string $cacheDirectory, array $defaultOptions = [])
    {
        $store = new Psr6Store(['cache_directory' => $cacheDirectory]);
        $this->client = new SymfonyCachingHttpClient($client, $store, $defaultOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }
}
