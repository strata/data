<?php

declare(strict_types=1);

namespace Strata\Data\Exception;

class FailedGraphQLException extends FailedRequestException
{
    protected ?string $lastQuery = null;

    /**
     * FailedRequestException constructor.
     *
     * @param $message Exception error message
     * @param ?string $lastQuery Last GraphQL query (auto-appended to error message)
     * @param array $errorData Error data returned by response
     * @param array $partialData Any partially returned data
     * @param \Exception|null $previous Previous exception
     */
    public function __construct($message, ?string $lastQuery = null, array $errorData = [], array $partialData = [], \Exception $previous = null)
    {
        if (null !== $lastQuery) {
            $this->lastQuery = $lastQuery;
            $message .= PHP_EOL . PHP_EOL . 'Last GraphQL query: ' . $lastQuery;
        }

        parent::__construct($message, $errorData, $partialData, $previous);
    }
}
