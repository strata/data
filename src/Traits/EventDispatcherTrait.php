<?php

declare(strict_types=1);

namespace Strata\Data\Traits;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event subscriber functionality
 *
 * @package Strata\Data\Traits
 */
trait EventDispatcherTrait
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

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
     * If an event dispatcher is not set, then creates one
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        if (!($this->eventDispatcher instanceof EventDispatcherInterface)) {
            $this->eventDispatcher = new EventDispatcher();
        }
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