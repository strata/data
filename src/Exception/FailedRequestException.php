<?php

declare(strict_types=1);

namespace Strata\Data\Exception;

class FailedRequestException extends \Exception
{
    private array $errorData = [];
    private array $partialData = [];

    /**
     * FailedRequestException constructor.
     *
     * @param $message
     * @param array $errorData Error data returned by response
     * @param array $partialData Any partially returned data
     * @param \Exception|null $previous Previous exception
     */
    public function __construct($message, array $errorData = [], array $partialData = [], \Exception $previous = null)
    {
        $this->errorData = $errorData;
        $this->partialData = $partialData;

        parent::__construct($message, 0, $previous);
    }

    /**
     * Return error data
     *
     * @return array
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Return any partial data returned from request
     *
     * @return array
     */
    public function getPartialData(): array
    {
        return $this->partialData;
    }
}
