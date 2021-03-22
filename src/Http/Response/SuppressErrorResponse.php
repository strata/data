<?php
declare(strict_types=1);

namespace Strata\Data\Http\Response;

use Symfony\Component\HttpClient\Response\StreamableInterface;
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
class SuppressErrorResponse implements ResponseInterface, StreamableInterface
{
    use DecoratedResponseTrait;

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
        return $this->decorated->getHeaders(false);
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
        return $this->decorated->getContent(false);
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
        return $this->decorated->toArray(false);
    }

    /**
     * Casts the response to a PHP stream resource.
     *
     * @param bool $throw This parameter has no effect, $throw is always false
     * @return resource
     * @throws \Exception If method accessed when child $response object does not contain this method
     */
    public function toStream(bool $throw = true)
    {
        if (method_exists($this->decorated, 'toStream')) {
            return $this->decorated->toStream(false);
        } else {
            throw new \Exception('Method toStream does not exist on object ' . get_class($this->decorated));
        }
    }
}
