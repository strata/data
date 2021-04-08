<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Cache\DataCache;
use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\CacheException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Base functionality for data provider
 *
 * @package Strata\Data
 */
trait DataProviderCommonTrait
{
    protected string $uriSeparator = '/';
    protected string $baseUri;
    protected bool $suppressErrors = false;
    protected bool $cacheEnabled = false;
    protected ?DecoderInterface $defaultDecoder = null;
    protected ?DataCache $cache = null;

    /**
     * Set the base URI to use for all requests
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
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
        return rtrim($this->getBaseUri(), $this->uriSeparator) . $this->uriSeparator . ltrim($endpoint, $this->uriSeparator);
    }

    /**
     * Suppress errors for this request
     *
     * Useful for sub-requests from the main request
     *
     * @param bool $value
     */
    public function suppressErrors(bool $value = true)
    {
        $this->suppressErrors = $value;
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
     * Set and enable the cache
     *
     * @param CacheInterface $cache
     * @param int $defaultLifetime Default cache lifetime
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null)
    {
        $this->cache = new DataCache($cache, $defaultLifetime);
        $this->enableCache($defaultLifetime);
    }

    /**
     * Enable cache for subsequent data requests
     *
     * @param ?int $lifetime
     * @return DataProviderCommonTrait Fluent interface
     * @throws CacheException If cache not set
     */
    public function enableCache(?int $lifetime = null)
    {
        if (!($this->cache instanceof DataCache)) {
            throw new CacheException(sprintf('You must setup the cache via %s::setCache() before enabling it', get_class($this)));
        }
        $this->cacheEnabled = true;

        if ($lifetime !== null) {
            $this->cache->setLifetime($lifetime);
        }
    }

    /**
     * Disable cache for subsequent data requests
     *
     * @return DataProviderCommonTrait Fluent interface
     */
    public function disableCache()
    {
        $this->cacheEnabled = false;
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
     * Set default decoder
     *
     * @param DecoderInterface $decoder
     */
    public function setDefaultDecoder(DecoderInterface $decoder)
    {
        $this->defaultDecoder = $decoder;
    }

    /**
     * Return default decoder
     *
     * @return DecoderInterface|null
     */
    public function getDefaultDecoder(): ?DecoderInterface
    {
        return $this->defaultDecoder;
    }
}
