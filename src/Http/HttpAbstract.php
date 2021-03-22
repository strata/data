<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Psr\Cache\CacheItemInterface;
use Strata\Data\DataAbstract;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Event\DecodeEvent;
use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Exception\NotFoundException;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Response\SuppressErrorResponse;
use Strata\Data\Version;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\RetryableHttpClient;

/**
 * Abstract class to interact with an API over HTTP
 * @package Strata\Data
 */
abstract class HttpAbstract extends DataAbstract
{
    protected array $defaultOptions = [
        'headers' => [
            'User-Agent' => Version::USER_AGENT
        ]
    ];

    protected array $cacheableMethods = ['GET', 'HEAD'];

    protected ?HttpClientInterface $client = null;
    protected int $totalHttpRequests = 0;
    protected bool $retryFailedRequests = false;
    protected bool $lastRetryFailedRequests = false;

    /**
     * Constructor
     *
     * @param ?string $baseUri Base URI to run queries against
     * @param ?EventDispatcher $eventDispatcher
     */
    public function __construct(?string $baseUri = null, ?EventDispatcher $eventDispatcher = null)
    {
        if ($baseUri !== null) {
            $this->setBaseUri($baseUri);
        }
        if ($eventDispatcher !== null) {
            $this->setEventDispatcher($eventDispatcher);
        } else {
            $this->setEventDispatcher(new EventDispatcher());
        }
    }

    /**
     * Setup HTTP client
     *
     * It is the responsibility of child classes to setup a HTTP Client
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @return HttpClientInterface
     */
    abstract public function setupHttpClient(): HttpClientInterface;

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
     * Decode response
     *
     * @param ResponseInterface $response
     * @param DecoderInterface|null $decoder Optional decoder, if not set uses getDefaultDecoder()
     * @return mixed
     */
    public function decode($response, ?DecoderInterface $decoder = null)
    {
        if (!($response instanceof ResponseInterface)) {
            return $response;
        }

        if ($decoder instanceof DecoderInterface) {
            $data = $decoder->decode($response);
        } else {
            $data = $this->getDefaultDecoder()->decode($response);
        }

        $requestId = $response->getInfo('user_data');
        $this->dispatchEvent(new DecodeEvent($data, $requestId, $response->getInfo('url')), DecodeEvent::NAME);

        return $data;
    }

    /**
     * Check whether a response is failed and if so, throw a FailedRequestException exception
     *
     * @param ResponseInterface $response
     * @throws FailedRequestException
     * @return void
     */
    abstract public function throwExceptionOnFailedRequest(ResponseInterface $response): void;

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
     * Add default options to HTTP requests
     *
     * @param array $option
     */
    public function addDefaultOption(array $option)
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $option);
    }

    /**
     * Return default HTTP options
     *
     * @return array|array[]
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * Set whether to retry failed requests up to 3 times with an exponential delay between retries
     *
     * @see https://symfony.com/doc/current/http_client.html#retry-failed-requests
     * @param bool $value
     * @return $this
     */
    public function retryFailedRequests(bool $value = true): DataAbstract
    {
        $this->lastRetryFailedRequests = $this->retryFailedRequests;
        $this->retryFailedRequests = $value;
        return $this;
    }

    /**
     * Reset retry failed requests back to the previous value
     *
     * @return $this
     */
    public function resetRetryFailedRequests(): DataAbstract
    {
        $this->retryFailedRequests = $this->lastRetryFailedRequests;
        return $this;
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
     * Return HTTP client
     *
     * Connects to the HTTP client via HttpAbstract::setupHttpClient if it does not already exist
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        if ($this->client === null) {
            $this->setHttpClient($this->setupHttpClient());
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
     * Return total number of HTTP requests processed
     *
     * @return int
     */
    public function getTotalHttpRequests(): int
    {
        return $this->totalHttpRequests;
    }

    /**
     * Set default options to use with all HTTP requests
     *
     * Default options are only used if not already set, for example if you set a custom User-Agent this will not be
     * overridden.
     * @see HttpClientInterface::OPTIONS_DEFAULTS
     * @param array $options HTTP options to set defaults on
     * @return array
     */
    public function setDefaultHttpOptions(array $options): array
    {
        $defaults = $this->getDefaultOptions();
        foreach (array_keys(HttpClientInterface::OPTIONS_DEFAULTS) as $optionName) {
            if (!isset($defaults[$optionName])) {
                continue;
            }

            if (in_array($optionName, ['query', 'headers', 'extra'])) {
                foreach ($defaults[$optionName] as $item => $value) {
                    if (!isset($options[$optionName][$item])) {
                        $options[$optionName][$item] = $value;
                    }
                }
            } else {
                if (!isset($options[$optionName])) {
                    $options[$optionName] = $defaults[$optionName];
                }
            }
        }
        return $options;
    }

    /**
     * Prepare a request, but do not run it. Returns a populated response if found in cache.
     *
     * You can check if a response is populated from cache via $response->isHit(). If so, the response contains
     * full data.
     *
     * If cache is not used, or a cache hit is not found, run the live request via HttpAbstract::runRequest($response)
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
        $options = $this->setDefaultHttpOptions($options);
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
        } catch (TransportExceptionInterface $e) {
            // Low-level exception (e.g. malformed URL)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($e, $requestId, $response->getInfo('url')), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                throw new FailedRequestException('Failed HTTP request', [], [], $e);
            }
        } catch (HttpExceptionInterface|FailedRequestException $e) {
            // HTTP client exception (e.g. 404)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($e, $requestId, $response->getInfo('url'), [
                'code' => $response->getStatusCode(),
                'message' => $this->statusMessage($response->getStatusCode()),
            ]), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                if (substr((string) $response->getStatusCode(), 0, 1) === '4') {
                    throw new NotFoundException('Not Found HTTP error', $response->getStatusCode(), $e);
                }
                throw new FailedRequestException(sprintf('Failed HTTP request, HTTP status code %d', $response->getStatusCode()), [], [], $e);
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
        }

        if ($response->isCacheable()) {
            $this->cache->commit();
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
        }

        if ($response->isCacheable()) {
            $this->cache->commit();
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
        }

        if ($response->isCacheable()) {
            $this->cache->commit();
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
        $this->suppressErrors();
        $this->retryFailedRequests();
        $cacheEnabled = $this->isCacheEnabled();
        $this->disableCache();

        $response = $this->get($uri, [], $options);
        $exists = ($response->getStatusCode() === 200);

        $this->resetSuppressErrors();
        $this->resetRetryFailedRequests();
        if ($cacheEnabled) {
            $this->enableCache();
        }

        return $exists;
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
}
