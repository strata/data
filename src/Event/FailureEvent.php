<?php
declare(strict_types=1);

namespace Strata\Data\Event;

class FailureEvent extends ResponseEventAbstract
{
    const NAME = 'strata.data.failure';

    private ?\Exception $exception;

    public function __construct(Response $response, ?\Exception $exception, array $context = [])
    {
        $this->exception = $exception;

        parent::__construct($response, $context);
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
