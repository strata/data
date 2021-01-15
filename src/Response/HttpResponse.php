<?php
declare(strict_types=1);

namespace Strata\Data\Response;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Decorator class to add functionality to a standard HTTP response
 *
 * @package Strata\Data\Response
 */
class HttpResponse implements ResponseInterface
{
    private ResponseInterface $response;
    private bool $isSuccess = false;
    private string $errorMessage = '';
    private array $errorData = [];

    /**
     * Constructor
     *
     * @param ResponseInterface $response Response we are suppressing exceptions for
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function setSuccess(bool $success): void
    {
        $this->isSuccess = $success;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setErrorMessage(string $message): void
    {
        $this->errorMessage = $message;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorData(array $data): void
    {
        $this->errorData = $data;
    }

    public function addErrorDataFromString(string $data): void
    {
        $this->errorData[] = $data;
    }

    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Gets the HTTP status code of the response.
     *
     * @throws TransportExceptionInterface when a network error occurs
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Gets the HTTP headers of the response.
     *
     * @param bool $throw Whether an exception should be thrown on 3/4/5xx status codes
     * @return string[][] The headers of the response keyed by header names in lowercase
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function getHeaders(bool $throw = true): array
    {
        return $this->response->getHeaders($throw);
    }

    /**
     * Gets the response body as a string.
     *
     * @param bool $throw Whether an exception should be thrown on 3/4/5xx status codes
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function getContent(bool $throw = true): string
    {
        return $this->response->getContent($throw);
    }

    /**
     * Gets the response body decoded as array, typically from a JSON payload.
     *
     * @param bool $throw Whether an exception should be thrown on 3/4/5xx status codes
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function toArray(bool $throw = true): array
    {
        return $this->response->toArray($throw);
    }

    /**
     * Closes the response stream and all related buffers.
     */
    public function cancel(): void
    {
        $this->response->cancel();
    }

    /**
     * Returns info coming from the transport layer.
     *
     * @return array|mixed|null An array of all available info, or one of them when $type is
     *                          provided, or null when an unsupported type is requested
     */
    public function getInfo(string $type = null)
    {
        return $this->response->getInfo($type);
    }

    /**
     * Casts the response to a PHP stream resource.
     *
     * bool $throw Whether an exception should be thrown on 3/4/5xx status codes
     * @return resource
     * @throws \Exception If method accessed when child $response object does not contain this method
     */
    public function toStream(bool $throw)
    {
        if (method_exists($this->response, 'toStream')) {
            return $this->response->toStream($throw);
        } else {
            throw new \Exception('Method toStream does not exist on object ' . get_class($this->response));
        }
    }
}