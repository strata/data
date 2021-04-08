<?php

declare(strict_types=1);

namespace Strata\Data\Event;

class FailureEvent extends RequestEventAbstract
{
    const NAME = 'data.request.failure';

    private \Exception $exception;

    public function __construct(\Exception $exception, string $requestId, string $uri, array $context = [])
    {
        $this->exception = $exception;
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
