<?php

declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\BaseUriException;
use Strata\Data\Cache\DataCache;
use Strata\Data\Decode\DecoderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows you to decorate the data provider with another class to add extended functionality
 *
 * @package Strata\Data
 */
trait DataProviderDecoratorTrait
{
    protected DataProviderInterface $dataProvider;

    /**
     * Set the data provider used to run API queries
     *
     * @param DataProviderInterface $dataProvider
     */
    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    /**
     * Return the data provider used to run API queries
     *
     * @return DataProviderInterface
     */
    public function getDataProvider(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    /**
     * @inheritDoc
     */
    public function getBaseUri(): string
    {
        return $this->dataProvider->getBaseUri();
    }

    /**
     * @inheritDoc
     */
    public function getUri(?string $endpoint = null): string
    {
        return $this->dataProvider->getUri($endpoint);
    }

    /**
     * @inheritDoc
     */
    public function getRequestIdentifier(string $uri, array $context = []): string
    {
        return $this->dataProvider->getRequestIdentifier($uri, $context);
    }

    /**
     * @inheritDoc
     */
    public function suppressErrors(bool $value = true)
    {
        return $this->dataProvider->suppressErrors($value);
    }

    /**
     * @inheritDoc
     */
    public function isSuppressErrors(): bool
    {
        return $this->dataProvider->isSuppressErrors();
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDecoder(): ?DecoderInterface
    {
        return $this->dataProvider->getDefaultDecoder();
    }

    /**
     * @inheritDoc
     */
    public function decode($response, ?DecoderInterface $decoder = null)
    {
        return $this->dataProvider->decode($response, $decoder);
    }

    /**
     * @inheritDoc
     */
    public function isCacheEnabled(): bool
    {
        return $this->dataProvider->isCacheEnabled();
    }

    /**
     * @inheritDoc
     */
    public function setCache(CacheInterface $cache, ?int $defaultLifetime = null)
    {
        return $this->dataProvider->setCache($cache, $defaultLifetime);
    }

    /**
     * @inheritDoc
     */
    public function getCache(): DataCache
    {
        return $this->dataProvider->getCache();
    }

    /**
     * @inheritDoc
     */
    public function enableCache(?int $lifetime = null)
    {
        return $this->dataProvider->enableCache($lifetime);
    }

    /**
     * @inheritDoc
     */
    public function disableCache()
    {
        return $this->dataProvider->disableCache();
    }

    /**
     * @inheritDoc
     */
    public function setCacheTags(array $tags = [])
    {
        return $this->dataProvider->setCache($tags);
    }

    /**
     * @inheritDoc
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0)
    {
        return $this->dataProvider->addListener($eventName, $listener, $priority);
    }

    /**
     * @inheritDoc
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dataProvider->addSubscriber($subscriber);
    }

    /**
     * @inheritDoc
     */
    public function dispatchEvent(Event $event, string $eventName): Event
    {
        return $this->dataProvider->dispatchEvent($event, $eventName);
    }
}
