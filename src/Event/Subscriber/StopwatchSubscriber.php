<?php

declare(strict_types=1);

namespace Strata\Data\Event\Subscriber;

use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class StopwatchSubscriber implements EventSubscriberInterface
{
    private Stopwatch $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StartEvent::NAME    => 'start',
            SuccessEvent::NAME  => 'stop',
            FailureEvent::NAME  => 'stop',
        ];
    }

    public function start(StartEvent $event)
    {
        $this->stopwatch->start($event->getRequestId(), 'data');
    }

    public function stop(SuccessEvent $event)
    {
        $this->stopwatch->stop($event->getRequestId());
    }
}
