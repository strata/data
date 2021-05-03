<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Cache\DataCache;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Exception\CacheException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\EventDispatcher\Event;

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
     * Whether errors are suppressed
     *
     * @return bool
     */
    public function isSuppressErrors(): bool;

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
     * Return the cache
     *
     * @return DataCache
     */
    public function getCache(): DataCache;

    /**
     * Enable cache for subsequent data requests
     *
     * @param ?int $lifetime
     * @return DataProviderCommonTrait Fluent interface
     * @throws CacheException If cache not set
     */
    public function enableCache(?int $lifetime = null);

    /**
     * Disable cache for subsequent data requests
     *
     * @return DataProviderCommonTrait Fluent interface
     */
    public function disableCache();

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
}
