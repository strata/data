<?php

declare(strict_types=1);

namespace Strata\Data\Event\Subscriber;

use Psr\Log\LoggerInterface;
use Strata\Data\Event\FailureEvent;
use Strata\Data\Event\StartEvent;
use Strata\Data\Event\SuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoggerSubscriber implements EventSubscriberInterface
{
    const PREFIX = '(Strata Data) ';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StartEvent::NAME    => 'logStart',
            SuccessEvent::NAME  => 'logSuccess',
            FailureEvent::NAME  => 'logFailure',
        ];
    }

    public function logStart(StartEvent $event)
    {
        $this->logger->debug(self::PREFIX . sprintf('Starting request to: %s', $event->getUri()), $event->getContext());
    }

    public function logSuccess(SuccessEvent $event)
    {
        $this->logger->info(self::PREFIX . sprintf('Successful request to: %s', $event->getUri()), $event->getContext());
    }

    public function logFailure(FailureEvent $event)
    {
        $this->logger->error(self::PREFIX . sprintf('Failed request to: %s, Error: %s', $event->getUri(), $event->getException()->getMessage()), $event->getContext());
    }
}
