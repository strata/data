<?php

declare(strict_types=1);

namespace Strata\Data\Http;

use Laminas\Feed\Reader\Feed\FeedInterface;
use Strata\Data\DataProviderCommonTrait;
use Strata\Data\DataProviderInterface;
use Strata\Data\Decode\DecoderFactory;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\Rss;
use Strata\Data\Event\DecodeEvent;
use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Exception\NotFoundException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Response\SuppressErrorResponse;
use Strata\Data\Traits\EventDispatcherTrait;
use Strata\Data\Version;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\RetryableHttpClient;

/**
 * Class to interact with an API over HTTP
 * @package Strata\Data
 */
class Http implements DataProviderInterface
{
    use DataProviderCommonTrait;
    use EventDispatcherTrait;

    /**
     * Default HTTP options when creating new HttpClient objects for this data provider
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @var array|array[]
     */
    const DEFAULT_OPTIONS = [
        'headers' => [
            'User-Agent' => Version::USER_AGENT
        ]
    ];

    protected ?array $currentDefaultOptions = null;
    protected array $cacheableMethods = ['GET', 'HEAD'];
    protected ?HttpClientInterface $client = null;
    protected int $totalHttpRequests = 0;
    protected bool $retryFailedRequests = false;

    /**
     * Constructor
     *
     * @param ?string $baseUri Base URI to run queries against
     * @param array $options Symfony HttpClient options
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     */
    public function __construct(?string $baseUri = null, array $options = [])
    {
        if ($baseUri !== null) {
            $this->setBaseUri($baseUri);
        }
        if (!empty($options)) {
            $this->setDefaultOptions($options);
        }
    }


    /**
     * Set the base URI to use for all requests
     *
     * @param string $baseUri
     * @param array $options Symfony HttpClient options
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     */
    public function setBaseUri(string $baseUri, array $options = [])
    {
        $this->baseUri = $baseUri;
        if (!empty($options)) {
            $this->setDefaultOptions($options);
        }
    }

    /**
     * Set HTTP client
     *
     * @param HttpClientInterface $client
     */
    public function setHttpClient(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Return HTTP client
     *
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        if ($this->client === null) {
            $this->setHttpClient(HttpClient::create($this->getCurrentDefaultOptions()));
        }
        return $this->client;
    }

    /**
     * Return retryable HTTP client
     *
     * @see https://symfony.com/doc/current/http_client.html#retry-failed-requests
     * @return RetryableHttpClient
     */
    public function getRetryableHttpClient(): RetryableHttpClient
    {
        $client = $this->getHttpClient();
        if ($client instanceof RetryableHttpClient) {
            return $client;
        }

        return new RetryableHttpClient($client);
    }

    /**
     * Set default HTTP options, merging passed options in with fixed HTTP options for this data provider
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $this->currentDefaultOptions = $this->mergeHttpOptions(self::DEFAULT_OPTIONS, $options);
    }

    /**
     * Merge two HTTP option arrays, overriding any previously set HTTP options in $options with $newOptions
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS
     * @param array $options HTTP options array
     * @param array $newOptions HTTP options to merge in to original array
     * @return array Merged array of HTTP options
     */
    public function mergeHttpOptions(array $options, array $newOptions): array
    {
        foreach (array_keys(HttpClientInterface::OPTIONS_DEFAULTS) as $optionName) {
            if (!isset($newOptions[$optionName])) {
                continue;
            }
            if (in_array($optionName, ['query', 'headers', 'extra'])) {
                foreach ($newOptions[$optionName] as $item => $value) {
                    $options[$optionName][$item] = $value;
                }
            } else {
                $options[$optionName] = $newOptions[$optionName];
            }
        }
        return $options;
    }

    /**
     * Return default HTTP options
     *
     * @return array|array[]
     */
    public function getCurrentDefaultOptions(): array
    {
        if (null === $this->currentDefaultOptions) {
            $this->currentDefaultOptions = self::DEFAULT_OPTIONS;
        }
        return $this->currentDefaultOptions;
    }

    /**
     * Set HTTP methods that can be automatically cached
     *
     * @param array $methods
     * @throws CacheException
     */
    public function setCacheableMethods(array $methods)
    {
        $allowed = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'];
        $diff = array_diff($methods, $allowed);
        $valid = (count($diff) === 0);
        if (!$valid) {
            throw new CacheException(sprintf('Invalid HTTP method passed: %s', implode(', ', $diff)));
        }

        $this->cacheableMethods = $methods;
    }

    /**
     * Is this request cacheable?
     *
     * Defined as cache enabled and HTTP method is cacheable
     *
     * @param string $method
     * @return bool
     */
    public function isCacheableRequest(string $method): bool
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }
        return in_array($method, $this->getCacheableMethods());
    }

    /**
     * return cacheable HTTP methods
     *
     * @return array|string[]
     */
    public function getCacheableMethods(): array
    {
        return $this->cacheableMethods;
    }

    /**
     * Return default decoder to use to decode responses
     *
     * @param ResponseInterface $response
     * @return DecoderInterface
     * @throws DecoderException
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getDecoderFromHttpResponse(ResponseInterface $response): DecoderInterface
    {
        // Get decoder from response content type
        $decoder = DecoderFactory::fromResponse($response);

        // Get decoder from URI
        if (null === $decoder) {
            $decoder = DecoderFactory::fromFilename($response->getInfo('url'));
        }

        if ($decoder instanceof DecoderInterface) {
            return $decoder;
        }

        throw new DecoderException('Cannot determine decoder from response, you must pass a decoder to the decode() method or set one via setDefaultDecoder()');
    }

    /**
     * Decode response
     *
     * @param $response
     * @param DecoderInterface|null $decoder Optional decoder
     * @return mixed
     * @throws DecoderException If the decoder cannot be retrieved or there is an error decoding the string
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function decode($response, ?DecoderInterface $decoder = null)
    {
        if (!($response instanceof ResponseInterface)) {
            throw new DecoderException('Cannot decode response since is not a valid response object');
        }

        if (!($decoder instanceof DecoderInterface)) {
            $decoder = $this->getDefaultDecoder();
            if (null === $decoder) {
                $decoder = $this->getDecoderFromHttpResponse($response);
            }
        }

        // @todo run event pre-decode?

        $data = $decoder->decode($response);

        $requestId = $response->getInfo('user_data');
        $this->dispatchEvent(new DecodeEvent($data, $requestId, $response->getInfo('url')), DecodeEvent::NAME);

        return $data;
    }

    /**
     * Check whether a response is failed and if so, throw an exception
     *
     * @param ResponseInterface $response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function throwExceptionOnFailedRequest(ResponseInterface $response): void
    {
        // This will throw exception on error since checks for 200 status
        $response->getHeaders();
    }

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * Hash generated from: URI + GET params
     *
     * @param $method
     * @param $uri
     * @param array $context
     * @return string Unique identifier for this request
     */
    public function getRequestIdentifier(string $uri, array $context = []): string
    {
        if (!empty($options['query'])) {
            $uri .= '?' . urlencode($options['query']);
        }
        return ContentHasher::hash($uri);
    }

    /**
     * Set whether to retry failed requests up to 3 times with an exponential delay between retries
     *
     * @see https://symfony.com/doc/current/http_client.html#retry-failed-requests
     * @param bool $value
     */
    public function retryFailedRequests(bool $value = true)
    {
        $this->retryFailedRequests = $value;
    }

    /**
     * Whether this request should retry failed requests
     *
     * @return bool
     */
    public function isRetryFailedRequests(): bool
    {
        return $this->retryFailedRequests;
    }

    /**
     * Return total number of HTTP requests processed
     *
     * @return int
     */
    public function getTotalHttpRequests(): int
    {
        return $this->totalHttpRequests;
    }

    /**
     * Return HTTP status message
     *
     * Usage:
     * $this->statusMessage(301)
     *
     * Returns:
     * Moved Permanently
     *
     * @param int $code HTTP status code
     * @return string|null
     */
    public function statusMessage(int $code): ?string
    {
        if (isset(Response::$statusTexts[$code])) {
            return Response::$statusTexts[$code];
        }
        return null;
    }

    /**
     * Convenience function to return a header from a response
     *
     * @param ResponseInterface $response
     * @param string $header Header to retrieve
     * @return string|string[]|null Return string if single header value, return array if multiple header values, or null if doesn't exist
     * @throws TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getHeader(ResponseInterface $response, string $header)
    {
        $header = strtolower($header);
        $headers = $response->getHeaders();
        if (!isset($headers[$header])) {
            return null;
        }
        if (count($headers[$header]) === 1) {
            return $headers[$header][0];
        }
        return $headers[$header];
    }

    /**
     * Prepare a request, but do not run it. Returns a populated response if found in cache.
     *
     * You can check if a response is populated from cache via $response->isHit(). If so, the response contains
     * full data.
     *
     * If cache is not used, or a cache hit is not found, run the live request via Http::runRequest($response)
     *
     * Or request is automatically run when you access getHeaders(), getContent() or toArray() - however result is then
     * not saved to cache since skips runRequest method.
     *
     * Dispatches the event:
     * - data.request.start
     *
     * @param $method
     * @param string $uri
     * @param array $options
     * @return CacheableResponse
     * @throws BaseUriException
     * @throws TransportExceptionInterface
     */
    public function prepareRequest($method, string $uri, array $options = []): CacheableResponse
    {
        // Passed $options take precedence over defaults
        $options = $this->mergeHttpOptions($this->getCurrentDefaultOptions(), $options);

        $uri = $this->getUri($uri);

        // Set request ID to user_data field
        $requestId = $this->getRequestIdentifier($method . ' ' . $uri, $options);
        $options['user_data'] = $requestId;

        // Check cache
        if ($this->isCacheableRequest($method)) {
            $item = $this->getCache()->getItem($requestId);
            if ($item->isHit()) {
                $response = $this->cache->getResponseFromItem($item, $method, $uri, $options);
                $response = new CacheableResponse($response, true);
                $response->setCacheItem($item);
                return $response;
            }
        }

        // Retry failed requests
        if ($this->isRetryFailedRequests()) {
            $httpClient = $this->getRetryableHttpClient();
        } else {
            $httpClient = $this->getHttpClient();
        }

        $response = $httpClient->request($method, $uri, $options);
        $this->dispatchEvent(new StartEvent($requestId, $uri, ['method' => $method]), StartEvent::NAME);

        // Suppress errors if a sub-request
        if ($this->isSuppressErrors()) {
            $response = new SuppressErrorResponse($response);
        }

        $response = new CacheableResponse($response, false);

        // Set cache item, if cache enabled
        if ($this->isCacheableRequest($method)) {
            $response->setCacheItem($item);
        }

        return $response;
    }

    /**
     * Run a request
     *
     * Dispatches the events:
     * - data.request.success
     * - data.request.failure
     *
     * @param CacheableResponse $response
     * @return CacheableResponse
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws TransportExceptionInterface
     */
    public function runRequest(CacheableResponse $response): CacheableResponse
    {
        $failed = false;
        $requestId = $response->getInfo('user_data');

        try {
            $this->totalHttpRequests++;

            // Test request is successful and throw an exception if not
            $this->throwExceptionOnFailedRequest($response);
        } catch (TransportExceptionInterface $exception) {
            // Low-level exception (e.g. malformed URL)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($exception, $requestId, $response->getInfo('url')), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                throw new FailedRequestException('Failed HTTP request', [], [], $exception);
            }
        } catch (HttpExceptionInterface | FailedRequestException $exception) {
            // HTTP client exception (e.g. 404)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($exception, $requestId, $response->getInfo('url'), [
                'code' => $response->getStatusCode(),
                'message' => $this->statusMessage($response->getStatusCode()),
            ]), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                if (substr((string) $response->getStatusCode(), 0, 1) === '4') {
                    throw new NotFoundException('Not Found HTTP error', $response->getStatusCode(), $exception);
                }
                throw $exception;
            }
        }

        if (!$failed) {
            $this->dispatchEvent(new SuccessEvent($requestId, $response->getInfo('url'), [
                'code' => $response->getStatusCode(),
                'message' => $this->statusMessage($response->getStatusCode()),
            ]), SuccessEvent::NAME);
        }

        // Store to cache
        if ($response->isCacheable()) {
            $item = $this->cache->setResponseToItem($response->getCacheItem(), $response);

            // Save deferred, you need to run $this->cache->commit() to commit to cache
            $this->cache->saveDeferred($item);

            // Unset cache item on response to free memory
            $response->unsetCacheItem();
        }

        // Set cache hit to false, since have run request
        $response->setHit(false);

        return $response;
    }

    /**
     * Make a GET request
     *
     * @param string $uri URI relative to base URI
     * @param array $queryParams Array of query params to send with GET request
     * @param array $options
     * @return CacheableResponse
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function get(string $uri, array $queryParams = [], array $options = []): CacheableResponse
    {
        if (isset($options['query'])) {
            $options['query'] = array_merge($queryParams, $options['query']);
        } else {
            $options['query'] = $queryParams;
        }

        $response = $this->prepareRequest('GET', $uri, $options);
        if (!$response->isHit()) {
            $response = $this->runRequest($response);

            if ($response->isCacheable()) {
                $this->cache->commit();
            }
        }

        return $response;
    }

    /**
     * Make a POST request
     *
     * @param string $uri URI relative to base URI
     * @param array $postData Array of data to send with POST request
     * @param array $options
     * @return CacheableResponse
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function post(string $uri, array $postData = [], array $options = []): CacheableResponse
    {
        if (isset($options['body']) && is_array($options['body'])) {
            $options['body'] = array_merge($postData, $options['body']);
        } else {
            $options['body'] = $postData;
        }

        $response = $this->prepareRequest('POST', $uri, $options);
        if (!$response->isHit()) {
            $response = $this->runRequest($response);

            if ($response->isCacheable()) {
                $this->cache->commit();
            }
        }

        return $response;
    }

    /**
     * Make a HEAD request
     *
     * @param string $uri URI relative to base URI
     * @param array $options
     * @return CacheableResponse
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function head(string $uri, array $options = []): CacheableResponse
    {
        $response = $this->prepareRequest('HEAD', $uri, $options);
        if (!$response->isHit()) {
            $response = $this->runRequest($response);

            if ($response->isCacheable()) {
                $this->cache->commit();
            }
        }

        return $response;
    }

    /**
     * Test whether a URL exists (returns 200 status)
     *
     * This request is not cached
     *
     * This is a convenience function where you only need to test if a URL exists, it does not return body content
     * It retries failed HTTP requests up to 3 times (first retry = 1 second; third retry: 4 seconds)
     * It will follow redirects, up to a limit of 5 redirects
     *
     * @param string $uri URI to test
     * @param array $options HTTPClient options
     * @return bool
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws TransportExceptionInterface
     */
    public function exists(string $uri, array $options = []): bool
    {
        if (!isset($options['max_redirects'])) {
            $options['max_redirects'] = 5;
        }

        $suppressErrors = $this->isSuppressErrors();
        $retryFailedRequests = $this->isRetryFailedRequests();
        $cacheEnabled = $this->isCacheEnabled();
        $this->suppressErrors();
        $this->retryFailedRequests();
        $this->disableCache();

        $response = $this->get($uri, [], $options);
        $exists = ($response->getStatusCode() === 200);

        if (!$suppressErrors) {
            $this->suppressErrors(false);
        }
        if (!$retryFailedRequests) {
            $this->retryFailedRequests(false);
        }
        if ($cacheEnabled) {
            $this->enableCache();
        }

        return $exists;
    }

    /**
     * Get an RSS feed
     *
     * @param string $uri
     * @param array $options
     * @return FeedInterface
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws TransportExceptionInterface
     */
    public function getRss(string $uri, array $options = []): FeedInterface
    {
        $data = $this->get($uri, [], $options);
        return $this->decode($data, new Rss());
    }

    /**
     * Run a bulk set of GET requests concurrently and return a generator you can foreach over
     *
     * @param array $uris
     * @param array $options
     * @return \Generator Generator of CacheableResponse items
     * @throws BaseUriException
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws TransportExceptionInterface
     */
    public function getConcurrent(array $uris, array $options = []): \Generator
    {
        $responses = [];
        foreach ($uris as $uri) {
            $responses[$uri] = $this->prepareRequest('GET', $uri, $options);
        }

        /** @var ResponseInterface $response */
        foreach ($responses as $response) {
            if (!$response->isHit()) {
                $response = $this->runRequest($response);
            }
            yield $response;
        }

        if ($this->isCacheEnabled()) {
            $this->cache->commit();
        }
    }

}
