<?php

declare(strict_types=1);

namespace Strata\Data\Cache;

use Strata\Data\Exception\CacheException;
use Strata\Data\Helper\ContentHasher;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class to manage a history log for data
 *
 * Helps detect changes in data (must be a string or array)
 *
 * @package Strata\Data\DataCache
 */
class DataHistory
{
    const CACHE_KEY_PREFIX = 'history_';

    private CacheItemPoolInterface $cache;
    private int $cacheLifetime;
    private int $maxHistoryDays = 30;
    private array $historyItems = [];

    /**
     * Constructor
     *
     * @param CacheItemPoolInterface $cache PSR-6 cache
     * @param int $lifetime Default cache lifetime for data cache, defaults to 2 months if not set
     */
    public function __construct(CacheItemPoolInterface $cache, int $lifetime = 2 * CacheLifetime::MONTH)
    {
        $this->cache = $cache;
        $this->cacheLifetime = $lifetime;
    }

    /**
     * Return cache key with prefix
     *
     * @param $key
     * @return string
     */
    public function getKey($key): string
    {
        return self::CACHE_KEY_PREFIX . (string) $key;
    }

    /**
     * Set the cache lifetime, default is one year
     *
     * You can use class constants CacheLifetime::MINUTE, HOUR, DAY, WEEK, MONTH, YEAR
     *
     * @param int $lifetime
     */
    public function setCacheLifetime(int $lifetime)
    {
        $this->cacheLifetime = $lifetime;
    }

    /**
     * Set max number of days history to keep before deleting
     *
     * @param int $days
     */
    public function setMaxHistoryDays(int $days)
    {
        $this->maxHistoryDays = $days;
    }

    /**
     * Return the history log for this data item
     *
     * @param $key
     * @return array
     * @throws \Exception
     */
    public function getAll($key): array
    {
        $item = $this->cache->getItem($this->getKey($key));

        if ($item->isHit()) {
            $history = $item->get();
        } else {
            $history = [];
        }

        return $history;
    }

    /**
     * Whether the item is stored in the data history
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function hasItem($key): bool
    {
        $item = $this->cache->getItem($this->getKey($key));

        return $item->isHit();
    }

    /**
     * Return last history log item
     *
     * @param $key
     * @param string $field Either 'updated', 'content_hash', 'metadata' or null to return array of all history data
     * @return mixed|array|null Return last history item or null on failure
     * @throws CacheException
     */
    public function getLastItem($key, string $field = null)
    {
        $history = $this->getAll($key);
        $item = array_pop($history);

        if ($field === null) {
            return $item;
        }

        switch ($field) {
            case 'updated':
                return $item['updated'];
            case 'content_hash':
                return $item['content_hash'];
            case 'metadata':
                return $item['metadata'];
            default:
                throw new CacheException(sprintf('Cannot return history field "%s" since not set', $field));
        }
    }

    /**
     * Has the data item changed since the last history log item?
     *
     * @param $key
     * @param string|array $data
     * @return bool
     */
    public function isChanged($key, $data): bool
    {
        $lastItem = $this->getLastItem($key);
        if ($lastItem === null) {
            return true;
        }
        return ContentHasher::hasContentChanged($lastItem['content_hash'], $data);
    }

    /**
     * Is the data identical to the last history item log?
     *
     * @param $key
     * @param $data
     * @return bool
     */
    public function isIdentical($key, $data): bool
    {
        return !$this->isChanged($key, $data);
    }

    /**
     * Whether this key is new and has no history
     *
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public function isNew($key): bool
    {
        $history = $history = $this->getAll($key);
        return empty($history);
    }

    /**
     * Not implemented - may belong in its own class
     *
     * @todo Add functionality to check if a data item has been deleted: "a way to delete the data from our DB that has been removed in the external API"
     * @param $key
     * @return bool
     */
    public function isDeleted($key): bool
    {
        return false;
    }

    /**
     * Save a new entry in the history log for this data item
     *
     * This adds log entries to the cache commit queue for performance reasons.
     * You need to call DataHistory::commit() to save these to the cache.
     *
     * Deletes old entries in the content history log older than DataHistory::maxHistoryDays
     *
     * @param $key
     * @param string|array $data
     * @param array $metadata Additional metadata to save in history log
     * @return bool Whether log request has been successfully added to the queue
     */
    public function add($key, $data, array $metadata = []): bool
    {
        $now = new \DateTimeImmutable();
        $this->historyItems[] = [
            'key'   => (string) $key,
            'data'  => [
                // ISO 8601 date
                'updated'       => $now->format('c'),
                'content_hash'  => ContentHasher::hash($data),
                'metadata'      => $metadata,
            ]
        ];
        return true;
    }

    /**
     * Save content history logs to the cache
     *
     * @return bool Whether log requests have been successfully saved to the cache
     */
    public function commit(): bool
    {
        // Group by key
        $keys = [];
        foreach ($this->historyItems as $data) {
            $key = $data['key'];
            $data = $data['data'];

            if (isset($keys[$key])) {
                $history = $keys[$key];
            } else {
                $history = [];
            }

            $history[] = $data;
            $keys[$key] = $history;
        }

        // Save to cache
        foreach ($keys as $key => $newHistory) {
            $item = $this->cache->getItem($this->getKey($key));

            if ($item->isHit()) {
                $history = $item->get();
                $history = $this->purge($history);
            } else {
                $history = [];
            }

            $history = array_merge($history, $newHistory);
            $item->set($history);
            $item->expiresAfter($this->cacheLifetime);
            $this->cache->saveDeferred($item);
        }

        $this->historyItems = [];
        return $this->cache->commit();
    }

    /**
     * Clear all Data History cache entries
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Purge old items from a data history array
     *
     * @param array $history Data history array to purge
     * @param float $probability Set a value between 0 and 1.0 to run based on chance (0.4 = run on 40% of calls)
     * @param ?\DateTime $now Inject current date, useful for testing
     * @return array
     * @throws \Exception
     */
    public function purge(array $history, float $probability = 0.4, ?\DateTime $now = null): array
    {
        $number = mt_rand(0, 10);
        if ($number > $probability * 10) {
            return $history;
        }

        if ($now === null) {
            $now = new \DateTimeImmutable();
        }
        $oldest = $now->sub(new \DateInterval('P' . $this->maxHistoryDays . 'D'));

        foreach ($history as $key => $item) {
            $date = new \DateTimeImmutable($item['updated']);
            if ($date < $oldest) {
                unset($history[$key]);
            }
        }

        return $history;
    }
}
