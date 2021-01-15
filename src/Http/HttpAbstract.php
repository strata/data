<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use http\Env\Response;
use Strata\Data\Debug;
use Strata\Data\Decode\DecoderStrategy;
use Strata\Data\Item;
use Strata\Data\Response\HttpResponse;
use Strata\Data\Response\SuppressErrorResponse;
use Strata\Data\Traits\DebugTrait;
use Strata\Data\Version;
use Strata\Data\Traits\BaseUriTrait;
use Strata\Data\Traits\CheckPermissionsTrait;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpClient\CachingHttpClient;

/**
 * Abstract class to interact with an API over HTTP
 * @package Strata\Data
 */
abstract class HttpAbstract
{
    use CheckPermissionsTrait, BaseUriTrait, DebugTrait;

    public const OPTIONS_DEFAULTS = [
        'headers' => [
            'User-Agent' => Version::USER_AGENT
        ]
    ];

    private ?HttpClientInterface $client = null;
    /*
    private int $expectedResponseCode = 200;
    private array $ignoreErrorCodes = [401];
    */
    private bool $suppressErrors = false;
    private int $totalHttpRequests = 0;
    private ?DecoderStrategy $decoder = null;

    /**
     * Setup HTTP client
     *
     * This is the responsibility of child classes to setup a HTTP Client
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @return HttpClientInterface
     */
    abstract public function setupHttpClient(): HttpClientInterface;

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * E.g. Hash of Method + URI + GET params for Rest API requests
     * Use ContentHasher::hash($string) to return a hashed identifier
     *
     * @param $method
     * @param $uri
     * @param array $options
     * @return string
     */
    abstract public function getRequestIdentifier($method, $uri, array $options = []): string;

    /**
     * Setup decoder to use on body content of responses
     *
     * @return ?DecoderStrategy Decoder or null if body is not to be processed
     */
    abstract public function setupDecoder(): ?DecoderStrategy;

    /**
     * Return whether a response is considered successful
     *
     * Should set $response->setSuccess() to store success state
     * Should set $response->setErrorMessage() to store error message
     * Should set $response->setErrorData() or $response->setErrorDataFromString() to store error data
     *
     * @param HttpResponse $response
     * @return bool Whether the response is successful
     */
    abstract public function isSuccessful(HttpResponse $response): bool;


    public function setDecoder(DecoderStrategy $decoder)
    {
        $this->decoder = $decoder;
    }

    public function getDecoder(): ?DecoderStrategy
    {
        return $this->decoder;
    }

    /**
     * Set HTTP client
     * @param HttpClient $client
     */
    public function setClient(HttpClientInterface $client)
    {
        $this->client = $client;
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
            $this->setClient($this->setupHttpClient());
        }

        return $this->client;
    }

    /**
     * Whether to suppress exceptions from being raised for 3xx, 4xx, 5xx, or decoding JSON content errors
     *
     * @param bool $suppress
     */
    public function suppressErrors(bool $suppress): void
    {
        $this->suppressErrors = $suppress;
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
     * @see Symfony\Contracts\HttpClient\HttpClientInterface::OPTIONS_DEFAULTS
     * @param array $options HTTP options to set defaults on
     * @return array
     */
    public function setDefaultHttpOptions(array $options): array
    {
        foreach (array_keys(HttpClientInterface::OPTIONS_DEFAULTS) as $optionName) {
            if (!isset(self::OPTIONS_DEFAULTS[$optionName])) {
                continue;
            }

            if (in_array($optionName, ['query', 'headers', 'extra'])) {
                foreach (self::OPTIONS_DEFAULTS[$optionName] as $item => $value) {
                    if (!isset($options[$optionName][$item])) {
                        $options[$optionName][$item] = $value;
                    }
                }
            } else {
                if (!isset($options[$optionName])) {
                    $options[$optionName] = self::OPTIONS_DEFAULTS[$optionName];
                }
            }
        }
        return $options;
    }

    /**
     * Make an HTTP request
     *
     * Get request
     * Check status code
     * Return failed
     * Decode content
     *
     * @param $method
     * @param $uri
     * @param array $options
     * @return ResponseInterface
     * @throws TransportExceptionInterface Low-level HTTP transport issue (failed request)
     * @throws RedirectionException Error 3xx
     * @throws ClientException Error 4xx
     * @throws ServerException Error 5xx
     */
    public function request($method, $uri, array $options = []): HttpResponse
    {
        $options = $this->setDefaultHttpOptions($options);
        $requestId = $this->getRequestIdentifier($method, $uri, $options);
        $this->logStartRequest($requestId, $method, $uri, $options);

        $response = $this->getHttpClient()->request($method, $uri, $options);
        $this->totalHttpRequests++;

        // Add functionality to response via decorators
        $response = new HttpResponse($response);
        if ($this->suppressErrors) {
            $response = new SuppressErrorResponse($response);
        }

        // @todo need to check status code for failures before we process body

        // Decode body content
        if ($this->getDecoder() !== null) {
            // @todo $this->getDecoder()->decode(); Sets item content & metadata, but we're not using Items here...

        }

        // Determine whether response is successful
        if ($this->isSuccessful($response)) {

            // Logger & stopwatch only run if setup
            $this->logSuccessfulResponse($requestId, $method, $uri, $response);
            return $response;

        } else {
            $this->logFailedResponse($requestId, $method, $uri, $response);
            return $response;
        }


        // TODO FROM HERE

        // Build item to return
        $item = new Item($uri, $response->getContent(), $this->decoder);

        if (substr((string) $response->getStatusCode(), 0, 1) === '4') {
            throw new NotFoundException($message, $response->getStatusCode());
        } else {
            throw new FailedRequestException($message, $response->getStatusCode());
        }

        // Return empty response for expected errors ???
        if (in_array($response->getStatusCode(), $this->ignoreErrorCodes)) {
            // EmptyResponse [] or '' if JSON or anything else
            //return new Response($response->getStatusCode(), [], '[]', '1.1', $response->getReasonPhrase());
        }
    }

    /**
     * Make a GET request
     *
     * @param string $uri URI relative to base URI
     * @param array $queryParams Array of query params to send with GET request
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function get(string $uri, array $queryParams = [], array $options = []): ResponseInterface
    {
        if (isset($options['query'])) {
            $options['query'] = array_merge($queryParams, $options['query']);
        } else {
            $options['query'] = $queryParams;
        }

        return $this->request('GET', $uri, $options);
    }

    /**
     * Make a POST request
     *
     * @param string $uri URI relative to base URI
     * @param array $postData Array of data to send with POST request
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function post(string $uri, array $postData = [], array $options = []): ResponseInterface
    {
        if (isset($options['body']) && is_array($options['body'])) {
            $options['body'] = array_merge($postData, $options['body']);
        } else {
            $options['body'] = $postData;
        }

        return $this->request('POST', $uri, $options);
    }

    /**
     * Make a HEAD request
     *
     * @param string $uri URI relative to base URI
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function head(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    /**
     * Output summary information to the logger
     *
     * Typically you should call this method at the end of a set of requests
     */
    public function logSummary()
    {
        if (!$this->hasLogger()) {
            return;
        }

        if ($this->hasStopwatch()) {
            $events = $this->getStopwatch()->getSectionEvents('requests');
            $totalTime = 0;
            $totalMemory = 0;
            array_walk($events, function (StopwatchEvent $event) use ($totalTime, $totalMemory) {
                $totalTime += $event->getDuration();
                $totalMemory += $event->getMemory() / 1024 / 1024;
            });
            $this->getLogger()->info(sprintf('Run %s HTTP requests: %.2F MiB - %d ms'), $this->totalHttpRequests, $totalMemory, $totalTime);

        } else {
            $this->getLogger()->info(sprintf('Run %s HTTP requests'), $this->totalHttpRequests);
        }
    }

}