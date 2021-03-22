<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Cache\CacheLifetime;
use Strata\Data\Cache\DataCache;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base functionality for data provider
 *
 * @package Strata\Data
 */
abstract class DataAbstract implements DataInterface
{
    const URI_SEPARATOR = '/';

    protected bool $suppressErrors = false;
    protected bool $lastSuppressErrors = false;
    protected bool $cacheEnabled = false;
    protected string $baseUri;
    protected ?DataCache $cache = null;
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Suppress errors for this request
     *
     * Useful for sub-requests from the main request
     *
     * @param bool $value
     * @return $this
     */
    public function suppressErrors(bool $value = true): DataAbstract
    {
        $this->lastSuppressErrors = $this->suppressErrors;
        $this->suppressErrors = $value;
        return $this;
    }

    /**
     * Reset suppress errors back to the previous value
     *
     * @return $this
     */
    public function resetSuppressErrors(): DataAbstract
    {
        $this->suppressErrors = $this->lastSuppressErrors;
        return $this;
    }

    /**
     * Whether errors are suppressed
     *
     * @return bool
     */
    public function isSuppressErrors(): bool
    {
        return $this->suppressErrors;
    }

    /**
     * Set the cache
     *
     * @param CacheInterface $cache
     * @param int $defaultLifetime Default cache lifetime
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null)
    {
        $this->cache = new DataCache($cache, $defaultLifetime);
    }

    /**
     * Enable cache for subsequent data requests
     *
     * @param ?int $lifetime
     * @return DataAbstract Fluent interface
     * @throws CacheException If cache not set
     */
    public function enableCache(?int $lifetime = null): DataAbstract
    {
        if (!($this->cache instanceof DataCache)) {
            throw new CacheException(sprintf('You must setup the cache via %s::setCache() before enabling it', get_class($this)));
        }
        $this->cacheEnabled = true;
        return $this;
    }

    /**
     * Disable cache for subsequent data requests
     *
     * @return DataAbstract Fluent interface
     */
    public function disableCache(): DataAbstract
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * Is the cache enabled?
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Set cache tags to apply to all future saved cache items
     *
     * To remove tags do not pass any arguments and tags will be reset to an empty array
     *
     * @param array $tags
     * @throws CacheException
     */
    public function setCacheTags(array $tags = [])
    {
        $this->getCache()->setTags($tags);
    }

    /**
     * Return the cache
     *
     * @return DataCache
     */
    public function getCache(): DataCache
    {
        return $this->cache;
    }

    /**
     * Set the base URI to use for all requests
     * @param string $baseUri
     * @return $this
     */
    public function setBaseUri(string $baseUri): DataAbstract
    {
        $this->baseUri = $baseUri;
        return $this;
    }

    /**
     * Return base URI to use for all data requests
     * @return string
     * @throws BaseUriException If base URI not set
     */
    public function getBaseUri(): string
    {
        if (empty($this->baseUri)) {
            throw new BaseUriException(sprintf('Base URI not set, please set via %s::setBaseUri()', get_class($this)));
        }

        return $this->baseUri;
    }

    /**
     * Return URI to use for current data request
     *
     * @param string|null $endpoint Optional endpoint to append to base URI
     * @return string
     * @throws BaseUriException If base URI not set
     */
    public function getUri(?string $endpoint = null): string
    {
        if ($endpoint === null) {
            return $this->getBaseUri();
        }
        return rtrim($this->getBaseUri(), self::URI_SEPARATOR) . self::URI_SEPARATOR . ltrim($endpoint, self::URI_SEPARATOR);
    }

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * @param $uri
     * @param array $context
     * @return string Unique identifier for this request
     */
    abstract public function getRequestIdentifier(string $uri, array $context = []): string;

    /**
     * Decode response
     *
     * @param mixed $response
     * @param DecoderInterface|null $decoder Optional decoder, if not set uses getDefaultDecoder()
     * @return mixed
     */
    abstract public function decode($response, ?DecoderInterface $decoder = null);

    /**
     * Return default decoder to use to decode responses
     *
     * @return DecoderInterface
     */
    abstract public function getDefaultDecoder(): DecoderInterface;

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): DataAbstract
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
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

}
