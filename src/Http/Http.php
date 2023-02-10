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
use Strata\Data\Event\RequestEventAbstract;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\HttpException;
use Strata\Data\Exception\HttpOptionException;
use Strata\Data\Exception\HttpTransportException;
use Strata\Data\Exception\HttpNotFoundException;
use Strata\Data\Exception\InvalidHttpMethodException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Helper\UnionTypes;
use Strata\Data\Http\Response\CacheableResponse;
use Strata\Data\Http\Response\DecoratedResponseTrait;
use Strata\Data\Http\Response\SuppressErrorResponse;
use Strata\Data\Traits\EventDispatcherTrait;
use Strata\Data\Transform\PropertyAccessorTrait;
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
    protected array $defaultOptions = [];

    protected ?string $userAgent = null;
    protected ?array $currentDefaultOptions = null;
    protected array $cacheableMethods = ['GET', 'HEAD'];
    protected ?HttpClientInterface $client = null;
    protected int $totalHttpRequests = 0;
    protected bool $retryFailedRequests = false;
    protected RequestTrace $requestTrace;

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
        $this->requestTrace = new RequestTrace();
    }

    /**
     * Return user agent to use with HTTP requests
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        if (null !== $this->userAgent) {
            return $this->userAgent;
        }
        $this->userAgent = Version::getUserAgent();
        return $this->userAgent;
    }

    /**
     * Set user agent to use with HTTP requests
     *
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;

        if (null !== $this->currentDefaultOptions) {
            $this->currentDefaultOptions['headers']['User-Agent'] = $userAgent;
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
     * Does this data provider have a HTTP client set?
     * @return bool
     */
    public function hasHttpClient(): bool
    {
        return $this->client !== null;
    }

    /**
     * Return HTTP client
     *
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        if (!$this->hasHttpClient()) {
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
     * Set default HTTP options for all subsequent HTTP requests
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $this->currentDefaultOptions = $this->mergeHttpOptions($this->getCurrentDefaultOptions(), $options);
    }

    /**
     * Remove a default HTTP option from all subsequent HTTP requests
     * @param string|array $option String option name (e.g. 'auth_bearer') or a two-element array to represent a multidimensional option (e.g. ['headers', 'User-Agent'])
     */
    public function removeDefaultOption($option)
    {
        if (is_string($option)) {
            if (isset($this->currentDefaultOptions[$option])) {
                unset($this->currentDefaultOptions[$option]);
            }
            return;
        }
        if (is_array($option)) {
            $parent = $option[0];
            $child = $option[1];
            if (isset($this->currentDefaultOptions[$parent][$child])) {
                unset($this->currentDefaultOptions[$parent][$child]);
            }
            return;
        }
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
        foreach (HttpClientInterface::OPTIONS_DEFAULTS as $httpOption => $httpOptionValue) {
            if (!isset($newOptions[$httpOption])) {
                continue;
            }
            if (is_array($httpOptionValue)) {
                if (!is_array($newOptions[$httpOption])) {
                    continue;
                }
                foreach ($newOptions[$httpOption] as $item => $value) {
                    $options[$httpOption][$item] = $value;
                }
            } else {
                $options[$httpOption] = $newOptions[$httpOption];
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
        // Set defaults the first time this is called
        if (null === $this->currentDefaultOptions) {
            $this->currentDefaultOptions = $this->defaultOptions;
            $this->currentDefaultOptions['headers']['User-Agent'] = $this->getUserAgent();
        }
        return $this->currentDefaultOptions;
    }

    /**
     * Set HTTP methods that can be automatically cached
     *
     * @param array $methods
     * @throws InvalidHttpMethodException
     */
    public function setCacheableMethods(array $methods)
    {
        self::validMethod($methods, true);
        $this->cacheableMethods = $methods;
    }

    /**
     * Is the method a valid HTTP method?
     *
     * @param array|string $methods HTTP method name or array of HTTP methods
     * @param bool $throw Throw InvalidHttpMethodException exception on failed validation
     * @return bool
     * @throws InvalidArgumentException
     * @throws InvalidHttpMethodException
     */
    public static function validMethod($methods, bool $throw = false): bool
    {
        UnionTypes::assert('$methods', $methods, 'array', 'string');

        if (!is_array($methods)) {
            $methods = [$methods];
        }
        array_walk($methods, function (&$value) {
            $value = (string) $value;
            $value = strtoupper($value);
        });

        $allowed = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'PATCH', 'PURGE', 'TRACE'];
        $diff = array_diff($methods, $allowed);
        $result = (count($diff) === 0);

        if ($throw && !$result) {
            throw new InvalidHttpMethodException(sprintf('Invalid HTTP method/s passed: %s', implode(', ', $diff)));
        }

        return $result;
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
        if (!empty($context['query'])) {
            $uri .= '?' . http_build_query($context['query']);
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
     * @param ?bool $cacheable Whether this request is cacheable, if null use Http::isCacheableRequest
     * @return CacheableResponse
     * @throws BaseUriException
     * @throws TransportExceptionInterface
     */
    public function prepareRequest($method, string $uri, array $options = [], ?bool $cacheable = null): CacheableResponse
    {
        // Passed $options take precedence over defaults
        $options = $this->mergeHttpOptions($this->getCurrentDefaultOptions(), $options);

        $uri = $this->getUri($uri);

        // Set request ID to user_data field
        $requestId = $this->getRequestIdentifier($method . ' ' . $uri, $options);
        $options['user_data'] = $requestId;

        // Check cache
        if ($cacheable === null) {
            $cacheable = $this->isCacheableRequest($method);
        }
        if (!$this->isCacheEnabled()) {
            $cacheable = false;
        }
        if ($cacheable) {
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
        $this->requestTrace->addRequest($requestId, $uri, $method, $options);

        // Suppress errors if a sub-request
        if ($this->isSuppressErrors()) {
            $response = new SuppressErrorResponse($response);
        }

        $response = new CacheableResponse($response, false);

        // Set cache item, if cache enabled
        if ($cacheable) {
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
     * @throws HttpException
     * @throws HttpNotFoundException
     * @throws TransportExceptionInterface
     */
    public function runRequest(CacheableResponse $response): CacheableResponse
    {
        $failed = false;
        $requestId = $response->getInfo('user_data');

        // Do nothing is response was returned in cache in prepareRequest()
        if ($response->isHit()) {
            $this->dispatchEvent(new SuccessEvent($requestId, $response->getInfo('url'), [
                'code' => $response->getStatusCode(),
                'message' => $this->statusMessage($response->getStatusCode()),
            ]), SuccessEvent::NAME);
            return $response;
        }

        try {
            $this->totalHttpRequests++;

            // Test request is successful and throw an exception if not
            $this->throwExceptionOnFailedRequest($response);
        } catch (TransportExceptionInterface $exception) {
            // Low-level exception (e.g. malformed URL)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($exception, $requestId, $response->getInfo('url')), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                throw new HttpTransportException(
                    sprintf('Failed HTTP transport request: %s', $exception->getMessage()),
                    $this->requestTrace->getRequestUri($requestId),
                    $this->requestTrace->getRequestMethod($requestId),
                    $this->requestTrace->getRequestOptions($requestId),
                    $response,
                    [],
                    [],
                    $exception,
                );
            }
        } catch (HttpException $exception) {
            // Request exception defined by child Http::throwExceptionOnFailedRequest() class
            $failed = true;
            $context = [
                'request_trace' => $exception->getRequestTrace()
            ];
            $this->dispatchEvent(new FailureEvent($exception, $requestId, $response->getInfo('url'), $context), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                throw $exception;
            }
        } catch (HttpExceptionInterface $exception) {
            // HTTP client exception (e.g. 404)
            $failed = true;
            $this->dispatchEvent(new FailureEvent($exception, $requestId, $response->getInfo('url'), [
                'code' => $response->getStatusCode(),
                'message' => $this->statusMessage($response->getStatusCode()),
            ]), FailureEvent::NAME);

            if (!$this->isSuppressErrors()) {
                if (substr((string) $response->getStatusCode(), 0, 3) === '404') {
                    throw new HttpNotFoundException(
                        'Not Found HTTP error',
                        $this->requestTrace->getRequestUri($requestId),
                        $this->requestTrace->getRequestMethod($requestId),
                        $this->requestTrace->getRequestOptions($requestId),
                        $response,
                        [],
                        [],
                        $exception,
                    );
                }
                throw new HttpException(
                    sprintf('Failed HTTP request: %s', $exception->getMessage()),
                    $this->requestTrace->getRequestUri($requestId),
                    $this->requestTrace->getRequestMethod($requestId),
                    $this->requestTrace->getRequestOptions($requestId),
                    $response,
                    [],
                    [],
                    $exception
                );
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

        $this->requestTrace->clearRequest($requestId);
        return $response;
    }

    /**
     * Make a GET request
     *
     * @param string $uri URI relative to base URI
     * @param array $queryParams Array of query params to send with GET request
     * @param array $options
     * @return CacheableResponse
     * @throws HttpException
     * @throws HttpNotFoundException
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
     * @param ?string|array $postData String body or array of data to send with POST request
     * @param array $options
     * @return CacheableResponse
     * @throws HttpException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function post(string $uri, $postData = null, array $options = []): CacheableResponse
    {
        if (is_array($postData)) {
            if (isset($options['body']) && is_array($options['body'])) {
                $options['body'] = array_merge($postData, $options['body']);
            } else {
                $options['body'] = $postData;
            }
        }
        if (is_string($postData)) {
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
     * @throws HttpException
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
     * @throws HttpException
     * @throws HttpNotFoundException
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
     * @throws HttpException
     * @throws HttpNotFoundException
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
     * @param array $uris Array of URI strings to query, or array of ['uri', 'options'] to run for each query
     * @param array $defaultOptions
     * @return \Generator Generator of CacheableResponse items
     * @throws BaseUriException
     * @throws HttpException
     * @throws HttpNotFoundException
     * @throws TransportExceptionInterface
     */
    public function getConcurrent(array $uris, array $defaultOptions = []): \Generator
    {
        $responses = [];
        foreach ($uris as $uri) {
            if (is_string($uri)) {
                $responses[] = $this->prepareRequest('GET', $uri, $defaultOptions);
            }
            if (is_array($uri) && isset($uri['uri']) && isset($uri['options'])) {
                $options = $this->mergeHttpOptions($defaultOptions, $uri['options']);
                $responses[] = $this->prepareRequest('GET', $uri['uri'], $options);
            }
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
