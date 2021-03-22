<?php
declare(strict_types=1);

namespace Strata\Data\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class RequestEventAbstract extends Event
{
    private string $requestId;
    private string $uri;
    private array $context;

    public function __construct(string $requestId, string $uri, array $context = [])
    {
        $this->uri = $uri;
        $this->context = $context;
    }

    /**
     * Return unique ID for this request
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->getRequestId();
    }

    /**
     * Return URI
     *
     * @return ResponseInterface
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Return array of contextual info
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
