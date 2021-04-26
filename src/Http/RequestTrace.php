<?php

declare(strict_types=1);

namespace Strata\Data\Http;

/**
 * Simple class to store request data for exception messages
 * @package Http
 */
class RequestTrace
{
    private array $requests = [];

    /**
     * Add information about an HTTP request for future use
     * @param string $requestId
     * @param string $uri
     * @param string $method
     * @param array $options
     */
    public function addRequest(string $requestId, string $uri, string $method, array $options = [])
    {
        $this->requests[$requestId] = [
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
        ];
    }

    /**
     * Return information about an HTTP request
     * @param string $requestId
     * @return mixed|null
     */
    public function getRequest(string $requestId)
    {
        if (isset($this->requests[$requestId])) {
            return $this->requests[$requestId];
        }
        return null;
    }

    /**
     * Clear information about an HTTP request
     * @param string $requestId
     */
    public function clearRequest(string $requestId)
    {
        if (isset($this->requests[$requestId])) {
            unset($this->requests[$requestId]);
        }
    }

    /**
     * Get request URI, or empty string if not set
     * @param string $requestId
     * @return string
     */
    public function getRequestUri(string $requestId): string
    {
        if (isset($this->requests[$requestId])) {
            return $this->requests[$requestId]['uri'];
        }
        return '';
    }

    /**
     * Get request method, or empty string if not set
     * @param string $requestId
     * @return string
     */
    public function getRequestMethod(string $requestId): string
    {
        if (isset($this->requests[$requestId])) {
            return $this->requests[$requestId]['method'];
        }
        return '';
    }

    /**
     * Get request options, or empty array if not set
     * @param string $requestId
     * @return array
     */
    public function getRequestOptions(string $requestId): array
    {
        if (isset($this->requests[$requestId])) {
            return $this->requests[$requestId]['options'];
        }
        return [];
    }
}
