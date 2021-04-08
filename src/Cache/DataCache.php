<?php

declare(strict_types=1);

namespace Strata\Data\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Strata\Data\Exception\CacheException;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class to manage cache for data
 *
 * Uses Symfony's PSR-6 cache pools
 *
 * @see https://symfony.com/doc/current/components/cache/cache_pools.html#using-psr-6
 * @package Strata\Data\Cache
 */
class DataCache implements CacheItemPoolInterface, TagAwareAdapterInterface, PruneableInterface
{
    private CacheItemPoolInterface $cache;
    protected array $tags = [];
    protected int $lifetime;

    /**
     * Constructor
     *
     * @param CacheItemPoolInterface $cache
     * @param ?int $defaultLifetime Default cache lifetime for data cache, defaults to 1 hour if not set
     */
    public function __construct(CacheItemPoolInterface $cache, ?int $defaultLifetime = null)
    {
        if ($defaultLifetime === null) {
            $defaultLifetime = CacheLifetime::HOUR;
        }
        $this->cache = $cache;
        $this->setLifetime($defaultLifetime);
    }

    /**
     * Set cache lifetime
     *
     * @param int $lifetime Lifetime in seconds
     * @return $this]
     */
    public function setLifetime(int $lifetime)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Are tags enabled by the cache adapter?
     *
     * @return bool
     */
    public function isTaggable(): bool
    {
        return ($this->cache instanceof TagAwareAdapterInterface);
    }

    /**
     * Is the prune operation supported by the cache adapter?
     *
     * @return bool
     */
    public function isPruneable(): bool
    {
        return ($this->cache instanceof PruneableInterface);
    }

    /**
     * Set tags to use when storing data requests in cache
     *
     * These apply to all subsequent requests, pass an empty array to remove current tags
     *
     * @param array $tags
     * @return $this
     */
    public function setTags(array $tags)
    {
        if (!$this->isTaggable()) {
            throw new CacheException(sprintf('Tags are not supported by your cache adapter %s', get_class($this->cache)));
        }
        $this->tags = $tags;
    }

    /**
     * Apply cache lifetime and tags defaults to an item before storing in the cache
     *
     * @param ItemInterface $item
     * @return ItemInterface
     * @throws InvalidArgumentException
     * @throws \Psr\Cache\CacheException
     */
    public function setCacheItemDefaults(ItemInterface $item): ItemInterface
    {
        $item->expiresAfter($this->lifetime);
        if ($this->isTaggable() && !empty($this->tags)) {
            $item->tag($this->tags);
        }
        return $item;
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItem($key): CacheItemInterface
    {
        return $this->cache->getItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function getItems(array $keys = array())
    {
        return $this->cache->getItems($keys);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function hasItem($key)
    {
        return $this->cache->hasItem($key);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(string $prefix = '')
    {
        return $this->cache->clear($prefix);
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        $item = $this->setCacheItemDefaults($item);
        return $this->cache->save($item);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $item = $this->setCacheItemDefaults($item);
        return $this->cache->saveDeferred($item);
    }

    /**
     * Save HTTP response to cache item for later hydration
     *
     * @param CacheItem $item Cache item to save response to
     * @param ResponseInterface $response Response to save to cache item
     * @return CacheItem
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function setResponseToItem(CacheItem $item, ResponseInterface $response): CacheItem
    {
        $item->set([
            'http_code'         => $response->getStatusCode(),
            'response_headers'  => $response->getHeaders(),
            'body'              => $response->getContent(),
        ]);
        return $item;
    }

    /**
     * Return hydrated HTTP response from cache item
     *
     * @param CacheItem $item Item to hydrate response from (must be set via DataCache::setResponseToCacheItem)
     * @param string $method Method of request, e.g. GET
     * @param string $uri URI of request
     * @param array $options Array of request options, defaults to []
     * @return ResponseInterface|null Response or null if cannot hydrate response from cache item
     */
    public function getResponseFromItem(CacheItem $item, string $method, string $uri, array $options = []): ?ResponseInterface
    {
        if (!$item->isHit()) {
            return null;
        }
        $content = $item->get();
        if (!is_array($content)) {
            return null;
        }
        $required = ['http_code', 'response_headers', 'body'];
        if (count(array_diff($required, array_keys($content))) > 0) {
            return null;
        }

        /**
         * @see Symfony\Component\HttpClient\CachingHttpClient::request Example usage of MockResponse
         */
        $response = new MockResponse($content['body'], [
            'http_code' => $content['http_code'],
            'response_headers' => $content['response_headers'],
        ]);
        $response = MockResponse::fromRequest($method, $uri, $options, $response);
        return $response;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        return $this->cache->commit();
    }

    /**
     * Delete cache items by tag
     *
     * @param array $tags
     * @return bool True on success
     */
    public function invalidateTags(array $tags): bool
    {
        if (!$this->isTaggable()) {
            throw new CacheException('Cannot prune cache since cache adaptor does not implement TagAwareAdapterInterface');
        }

        /** @var TagAwareAdapterInterface */
        return $this->cache->invalidateTags($tags);
    }

    /**
     * Prune old cache items from data and history cache
     *
     * For filesystem cache, helps avoid filling up cache
     *
     * To only run a prune request on the cache a percentage of times this method is called, you can pass the
     * $probability argument which is represents a percentage between 0 (never runs) and 1 (always runs).
     *
     * For example, to run 1 time in 10:
     * $this->prune(0.1);
     *
     * @param float $probability Set a value between 0 and 1 to run based on a % chance (0.5 = run on 50% of calls)
     * @see https://symfony.com/doc/current/components/cache/cache_pools.html#component-cache-cache-pool-prune
     */
    public function prune(float $probability = 1.0)
    {
        if (!$this->isPruneable()) {
            throw new CacheException('Cannot prune cache since cache adaptor does not implement PruneableInterface');
        }

        if (!($probability > 0) || $probability > 1) {
            throw new CacheException('The $probability value must be higher than 0 and no higher than 1');
        }

        $number = mt_rand(0, 10);
        if ($number > $probability * 10) {
            return;
        }

        /** @var PruneableInterface */
        $this->cache->prune();
    }
}
