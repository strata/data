<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Cache\DataCache;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Strata\Data\Http\Response\CacheableResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface DataProviderInterface
{
    /**
     * Return base URI to use for all data requests
     * @return string
     * @throws BaseUriException If base URI not set
     */
    public function getBaseUri(): string;

    /**
     * Return URI to use for current data request
     *
     * @param string|null $endpoint Optional endpoint to append to base URI
     * @return string
     * @throws BaseUriException If base URI not set
     */
    public function getUri(?string $endpoint = null): string;

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * @param $uri
     * @param array $context
     * @return string
     */
    public function getRequestIdentifier(string $uri, array $context = []): string;

    /**
     * Suppress errors for this request
     *
     * Useful for sub-requests from the main request
     *
     * @param bool $value
     */
    public function suppressErrors(bool $value = true);

    /**
     * Whether errors are suppressed
     *
     * @return bool
     */
    public function isSuppressErrors(): bool;

    /**
     * Reset suppress errors status to last value
     *
     */
    public function resetSuppressErrors();

    /**
     * Return default decoder
     *
     * @return DecoderInterface|null
     */
    public function getDefaultDecoder(): ?DecoderInterface;

    /**
     * Decode response
     *
     * @param mixed $response
     * @param DecoderInterface|null $decoder Optional decoder, if not set uses getDefaultDecoder()
     * @return mixed
     */
    public function decode($response, ?DecoderInterface $decoder = null);

    /**
     * Is the cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled(): bool;

    /**
     * Set and enable the cache
     *
     * @param CacheInterface $cache
     * @param int $defaultLifetime Default cache lifetime
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null);

    /**
     * Whether the data provider has a cache set
     *
     * @return bool
     */
    public function hasCache(): bool;

    /**
     * Return the cache
     *
     * @return DataCache
     */
    public function getCache(): DataCache;

    /**
     * Enable cache for subsequent data requests
     *
     * @param ?int $lifetime
     * @return self Fluent interface
     * @throws CacheException If cache not set
     */
    public function enableCache(?int $lifetime = null): self;

    /**
     * Disable cache for subsequent data requests
     *
     * @return self Fluent interface
     */
    public function disableCache(): self;

    /**
     * Set cache tags to apply to all future saved cache items
     *
     * To remove tags do not pass any arguments and tags will be reset to an empty array
     *
     * @param array $tags
     * @throws CacheException
     */
    public function setCacheTags(array $tags = []);

    /**
     * Adds an event listener that listens on the specified event
     *
     * @param string $eventName Event name
     * @param callable $listener The listener
     * @param int      $priority The higher this value, the earlier an event
     *                           listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0);

    /**
     * Adds an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber);

    /**
     * Dispatches an event to all registered listeners
     *
     * @param Event $event The event to pass to the event handlers/listeners
     * @param string $eventName The name of the event to dispatch
     * @return Event The passed $event MUST be returned
     */
    public function dispatchEvent(Event $event, string $eventName): Event;

    public function runRequest(CacheableResponse $response): CacheableResponse;

    public function hasHttpClient(): bool;

    public function setHttpClient(HttpClientInterface $client);

    public function getHttpClient(): HttpClientInterface;

    public function prepareRequest($method, string $uri, array $options = [], ?bool $cacheable = null): CacheableResponse;
}
