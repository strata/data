<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\DataEvents;
use Strata\Data\Debug;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\PrepareEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Strata\Data\Model\Response;
use Strata\Data\Response\SuppressErrorResponse;
use Strata\Data\Version;
use Strata\Data\Traits\BaseUriTrait;
use Strata\Data\Traits\CheckPermissionsTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Exception\NotFoundException;

use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpClient\CachingHttpClient;

/**
 * Abstract class to interact with an API over HTTP
 * @package Strata\Data
 */
abstract class HttpAbstract
{
    use CheckPermissionsTrait, BaseUriTrait;

    private array $defaultOptions = [
        'headers' => [
            'User-Agent' => Version::USER_AGENT
        ]
    ];

    private ?HttpClientInterface $client = null;
    private EventDispatcherInterface $eventDispatcher;
    private bool $suppressErrors = false;
    private int $totalHttpRequests = 0;

    /**
     * Constructor
     *
     * @param ?string $baseUri Base URI to run queries against
     */
    public function __construct(?string $baseUri = null)
    {
        if ($baseUri !== null) {
            $this->setBaseUri($baseUri);
        }

        $this->setEventDispatcher(new EventDispatcher());
    }

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Return the event dispatcher
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Adds an event listener that listens on the specified events
     *
     * @param string $eventName Event name
     * @param callable $listener The listener
     * @param int      $priority The higher this value, the earlier an event
     *                           listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0)
    {
        return $this->getEventDispatcher()->addListener($eventName, $listener, $priority);
    }

    /**
     * Adds an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     * @return mixed
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->getEventDispatcher()->addSubscriber($subscriber);
    }

    /**
     * Dispatches an event to all registered listeners
     *
     * @param Event $event The event to pass to the event handlers/listeners
     * @param string $eventName The name of the event to dispatch
     * @return Event The passed $event MUST be returned
     */
    public function dispatchEvent(Event $event, string $eventName): Event
    {
        return $this->getEventDispatcher()->dispatch($event, $eventName);
    }

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
     * Return decoder to decode responses, override this in child classes
     *
     * @return ?DecoderInterface Decoder or null if body is not to be processed
     */
    public function getDefaultDecoder(): ?DecoderInterface
    {
        return null;
    }

    /**
     * Decode string response into a useful format
     *
     * @param string $content
     * @param DecoderInterface|null $decoder
     * @return mixed|string
     */
    public function decode(string $content, ?DecoderInterface $decoder = null)
    {
        if ($decoder === null) {
            $decoder = $this->getDefaultDecoder();
        }
        if ($decoder instanceof DecoderInterface) {
            return $decoder->decode($content);
        }
        return $content;
    }

    /**
     * Populate response with data content
     *
     * @param Response $response
     * @param ResponseInterface $httpResponse
     * @return void
     */
    abstract public function populateResponse(Response $response, ResponseInterface $httpResponse): void;

    /**
     * Check whether a response is failed and if so, throw a FailedRequestException
     *
     * @param Response $response
     * @return void
     * @throws FailedRequestException
     */
    abstract public function throwExceptionOnFailedRequest(Response $response): void;

    /**
     * Set HTTP client
     * @param HttpClient $client
     */
    public function setClient(HttpClientInterface $client)
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

    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
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
     * @return Response
     * @throws NotFoundException
     * @throws FailedRequestException
     */
    public function request($method, $uri, array $options = []): Response
    {
        $failed = false;
        $options = $this->setDefaultHttpOptions($options);
        $requestId = $this->getRequestIdentifier($method, $uri, $options);
        $response = new Response($requestId, $method . ' ' . $uri);
        $this->dispatchEvent(new StartEvent($response, $options), StartEvent::NAME);

        try {
            $httpResponse = $this->getHttpClient()->request($method, $this->getUri($uri), $options);
            $this->totalHttpRequests++;

            // Add functionality to response via decorators
            if ($this->suppressErrors) {
                $httpResponse = new SuppressErrorResponse($httpResponse);
            }

            //$response->setRawResponse($httpResponse);
            $response->setMetaFromArray($httpResponse->getHeaders());

            $this->populateResponse($response, $httpResponse);

            if ($this->suppressErrors) {
                try {
                    $this->throwExceptionOnFailedRequest($response);
                } catch (FailedRequestException $e) {
                    $failed = true;
                    $this->dispatchEvent(new FailureEvent($response, $e, ['HTTP status' => $httpResponse->getStatusCode()]), FailureEvent::NAME);
                }
            } else {
                $this->throwExceptionOnFailedRequest($response);
            }
        } catch (TransportExceptionInterface|ClientException $e) {
            $failed = true;
            $this->dispatchEvent(new FailureEvent($response, $e, ['HTTP status' => $httpResponse->getStatusCode()]), FailureEvent::NAME);

            if (!$this->suppressErrors) {
                if (substr((string) $httpResponse->getStatusCode(), 0, 1) === '4') {
                    throw new NotFoundException('Not Found HTTP error', $httpResponse->getStatusCode(), $e);
                }
                throw new FailedRequestException('Failed HTTP request', $httpResponse->getStatusCode(), $e);
            }
        }

        if (!$failed) {
            $this->dispatchEvent(new SuccessEvent($response, ['HTTP status' => $httpResponse->getStatusCode()]), SuccessEvent::NAME);
        }

        return $response;
    }

    /**
     * Make a GET request
     *
     * @param string $uri URI relative to base URI
     * @param array $queryParams Array of query params to send with GET request
     * @param array $options
     * @return Response
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function get(string $uri, array $queryParams = [], array $options = []): Response
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
     * @return Response
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function post(string $uri, array $postData = [], array $options = []): Response
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
     * @return Response
     * @throws FailedRequestException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function head(string $uri, array $options = []): Response
    {
        return $this->request('HEAD', $uri, $options);
    }

    /**
     * Output summary information to the logger
     *
     * @todo Test this and add sections
     *
     * Typically you should call this method at the end of a set of requests
     */
    public function logSummary()
    {
        if (!$this->hasLogger()) {
            return;
        }

        if ($this->hasStopwatch()) {
            $events = $this->getStopwatch()->getSectionEvents('http');
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
