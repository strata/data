<?php
declare(strict_types=1);

namespace Strata\Data\Subscriber;

use Psr\Log\LoggerInterface;
use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class LoggerSubscriber implements EventSubscriberInterface
{
    const PREFIX = '(Strata Data) ';

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
        $this->stopwatch->start($event->getResponse()->getRequestId(), 'data');
    }

    public function stop(SuccessEvent $event)
    {
        $this->stopwatch->stop($event->getResponse()->getRequestId());
    }

}