<?php
declare(strict_types=1);

namespace Strata\Data\Http\Response;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait DecoratedResponseTrait
{
    private ResponseInterface $decorated;

    /**
     * Constructor
     *
     * @param ResponseInterface $response Response we are decorating
     */
    public function __construct(ResponseInterface $response)
    {
        $this->decorated = $response;
    }

    /**
     * Gets the HTTP status code of the response.
     *
     * @throws TransportExceptionInterface when a network error occurs
     */
    public function getStatusCode(): int
    {
        return $this->decorated->getStatusCode();
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
        return $this->decorated->getHeaders($throw);
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
        return $this->decorated->getContent($throw);
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
        return $this->decorated->toArray($throw);
    }

    /**
     * Closes the response stream and all related buffers.
     */
    public function cancel(): void
    {
        $this->decorated->cancel();
    }

    /**
     * Returns info coming from the transport layer.
     *
     * @return array|mixed|null An array of all available info, or one of them when $type is
     *                          provided, or null when an unsupported type is requested
     */
    public function getInfo(string $type = null)
    {
        return $this->decorated->getInfo($type);
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