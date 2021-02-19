<?php
declare(strict_types=1);

namespace Strata\Data\Cache;

use Strata\Data\Helper\ContentHasher;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class to manage a history log for data
 *
 * Helps detect changes in data (must be a string or array)
 *
 * Usage:
 *
 * $history = new DataHistory($cache);
 *
 * // Find out if data has changed
 * $isChanged = $history->isChanged($id, $data);
 * $lastItem = $history->getLastItem($id);
 * $lastUpdated = $lastItem['updated'];
 *
 * // Log current data history
 * $history->log($id, $data);
 * $history->commit();
 *
 * @package Strata\Data\ContentCache
 */
class DataHistory
{
    const CACHE_KEY_PREFIX = 'DataHistory.';
    const DAY = 86400;
    const WEEK = 604800;
    const MONTH = 2678400;
    const YEAR = 31536000;
    const DATE_FORMAT_STRING = 'c';
    const DATE_FORMAT_KEY = 'YmdHis';

    private CacheItemPoolInterface $cache;
    private int $cacheLifetime = self::YEAR;
    private int $maxHistoryDays = 30;

    /**
     * Constructor
     *
     * @param CacheItemPoolInterface $cache PSR-6 cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->setCache($cache);
    }

    /**
     * Set the cache object
     *
     * @param CacheItemPoolInterface $cache PSR-6 cache
     */
    public function setCache(CacheItemPoolInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Set the cache lifetime, default is one year
     *
     * You can use class constants DataHistory::DAY, WEEK, MONTH, YEAR
     *
     * @param int $seconds
     */
    public function setCacheLifetime(int $seconds)
    {
        $this->cacheLifetime = $seconds;
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
     * @param string $identifier
     * @return array
     * @throws \Exception
     */
    public function getHistory(string $identifier): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $identifier);

        if ($item->isHit()) {
            $history = $item->get();
        } else {
            $history = [];
        }

        return $history;
    }

    /**
     * Return last history log item
     *
     * @param string $identifier
     * @return array|null Return last history item or null on failure
     * @throws \Exception
     */
    public function getLastItem(string $identifier): ?array
    {
        $history = $this->getHistory($identifier);
        return array_pop($history);
    }

    /**
     * Has the data item changed since the last history log item?
     *
     * @param string $identifier
     * @param string|array $data
     * @return bool
     */
    public function isChanged(string $identifier, $data): bool
    {
        $lastItem = $this->getLastItem($identifier);
        if ($lastItem === null) {
            return true;
        }
        return ContentHasher::hasContentChanged($lastItem['contentHash'], $data);
    }

    /**
     * Save a new entry in the history log for this data item
     *
     * This adds log entries to the cache commit queue for performance reasons.
     * You need to call DataHistory::commit() to save these to the cache.
     *
     * Deletes old entries in the content history log older than DataHistory::maxHistoryDays
     *
     * @param string $identifier
     * @param string|array $data
     * @param array $metadata Additional metadata to save in history log
     * @return bool Whether log request has been successfully added to the queue
     */
    public function log(string $identifier, $data, array $metadata = []): bool
    {
        $now = new \DateTimeImmutable();
        $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $identifier);

        if ($item->isHit()) {
            $history = $item->get();

            // Purge old items
            $oldest = $now->sub(new \DateInterval('P' . $this->maxHistoryDays . 'D'));
            $oldest = $oldest->format(self::DATE_FORMAT_KEY);
            foreach (array_keys($history) as $date) {
                if ($date < $oldest) {
                    unset($history[$date]);
                }
            }
        } else {
            $history = [];
        }

        $history[$now->format(self::DATE_FORMAT_KEY)] = [
            'updated'       => $now->format(self::DATE_FORMAT_STRING),
            'contentHash'   => ContentHasher::hash($data),
            'metaData'      => $metadata,
        ];

        $item->set($history);
        $item->expiresAfter($this->cacheLifetime);
        return $this->cache->saveDeferred($item);
    }

    /**
     * Save content history logs to the cache
     *
     * @return bool Whether log requests have been successfully saved to the cache
     */
    public function commit(): bool
    {
        return $this->cache->commit();
    }
}
