<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Helper\ContentHasher;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Cache
{
    private $cache;
    private $contentHasher;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->contentHasher = new ContentHasher();
    }

    /**
     * @param $key
     * @param callable $callback
     * @param int|\DateInterval|null $expiresAfter
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     * @param array $tags
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($key, callable $callback, $expiresAfter = null, ?array $tags)
    {
        // The callable will only be executed on a cache miss.
        $value = $this->cache->get($key, function (ItemInterface $item) use ($callback, $expiresAfter, $tags) {

            // Set cache options
            if ($expiresAfter !== null) {
                $item->expiresAfter($expiresAfter);
            } else {
                $item->expiresAfter(DEFAULT_EXPIRES_AFTER);
            }

            if (!empty($tags)) {
                $item->tag($tags);
            }

            // Retrieve value from callback
            $value = $callback();
        });

        // Unpack value

        echo $value; // 'foobar'

    }

    public function packContent($content)
    {
        // @todo Store content hash, work out if data has changed since last request?

        return [
            'hash' => $this->contentHasher($content),
            'lastRetrieved' => new \DateTime(),
            'content' => $content,
        ];
    }

    public function unpackContent($content)
    {

    }

}