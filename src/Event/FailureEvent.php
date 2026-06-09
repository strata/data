<?php

declare(strict_types=1);

namespace Strata\Data\Event;

class FailureEvent extends RequestEventAbstract
{
    const NAME = 'data.request.failure';

    public function __construct(private \Exception $exception, string $requestId, string $uri, array $context = [])
    {
        parent::__construct($requestId, $uri, $context);
    }

    /**
     * Return exception
     *
     * @return \Exception
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }
}
