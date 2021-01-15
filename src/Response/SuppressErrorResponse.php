<?php
declare(strict_types=1);

namespace Strata\Data\Response;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Decorator class to suppress exceptions being raised in a HTTP response
 *
 * This only suppresses exceptions raised by 3xx, 4xx, 5xx, or decoding JSON content errors
 * Does not suppress TransportExceptionInterface
 *
 * @package Strata\Data\Response
 */
class SuppressErrorResponse implements ResponseInterface
{
    private ResponseInterface $response;

    /**
     * Constructor
     *
     * @param ResponseInterface $response Response we are suppressing exceptions for
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
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
     * Does not throw exceptions.
     *
     * @param bool $throw This parameter has no effect, $throw is always false
     * @return string[][] The headers of the response keyed by header names in lowercase
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function getHeaders(bool $throw = true): array
    {
        return $this->response->getHeaders(false);
    }

    /**
     * Gets the response body as a string.
     *
     * Does not throw exceptions.
     *
     * @param bool $throw This parameter has no effect, $throw is always false
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function getContent(bool $throw = true): string
    {
        return $this->response->getContent(false);
    }

    /**
     * Gets the response body decoded as array, typically from a JSON payload.
     *
     * Does not throw exceptions. Returns empty array on empty body.
     *
     * @param bool $throw This parameter has no effect, $throw is always false
     * @throws TransportExceptionInterface   When a network error occurs
     */
    public function toArray(bool $throw = true): array
    {
        if ('' === $content = $this->getContent(false)) {
            return [];
        }
        return $this->response->toArray(false);
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
     * @param bool $throw This parameter has no effect, $throw is always false
     * @return resource
     * @throws \Exception If method accessed when child $response object does not contain this method
     */
    public function toStream(bool $throw)
    {
        if (method_exists($this->response, 'toStream')) {
            return $this->response->toStream(false);
        } else {
            throw new \Exception('Method toStream does not exist on object ' . get_class($this->response));
        }
    }
}