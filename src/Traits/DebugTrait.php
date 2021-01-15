<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Debugger functionality (logger and profiler)
 *
 * Usage:
 * // Setup
 * $this->setLogger($logger);
 * $this->setStopwatch($stopwatch);
 *
 * // Start a HTTP request
 * $this->startRequestLog('GET', $url);
 *
 * // run request...
 *
 * // Log success state
 * $debug->logSuccessfulResponse('GET', $url, $response);
 *
 * // Or log a failed request
 * $debug->logFailedResponse('GET', $url, $response);
 *
 * @package Strata\Data\Traits
 */
trait DebugTrait
{
    private ?Stopwatch $stopwatch = null;
    private ?LoggerInterface $logger = null;

    /**
     * Whether a valid logger is set
     *
     * @return bool
     */
    public function hasLogger(): bool
    {
        return ($this->logger instanceof LoggerInterface);
    }

    /**
     * Whether a valid stopwatch is set
     *
     * @return bool
     */
    public function hasStopwatch(): bool
    {
        return ($this->stopwatch instanceof Stopwatch);
    }

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Return logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the stopwatch
     *
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch): void
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * Return the stopwatch
     *
     * @return Stopwatch
     */
    public function getStopwatch(): Stopwatch
    {
        return $this->stopwatch;
    }

    /**
     * Logs debug message & starts stopwatch profiler
     *
     * @param string $id
     * @param string $method
     * @param string $uri
     * @param array $options
     */
    public function logStartRequest(string $id, string $method, string $uri, array $options)
    {
        if ($this->hasLogger()) {
            $this->logger->debug(LOG_MESSAGE_PREFIX . sprintf('Attempting %s request to %s', $method, $uri), $options);
        }
        if ($this->hasStopwatch()) {
            $this->stopwatch->start($uri, 'data');
        }
    }

    /**
     * Logs info message on success & stops stopwatch profiler
     *
     * @param string $id
     * @param string $method
     * @param string $uri
     * @param ResponseInterface $response
     */
    public function logSuccessfulResponse(string $id, string $method, string $uri, ResponseInterface $response)
    {
        if ($this->hasLogger()) {
            $context = [['HTTP status code'] => $response->getStatusCode()];
            $this->logger->info(LOG_MESSAGE_PREFIX . sprintf('Successful %s request to %s', $method, $uri), $context);
        }
        if ($this->hasStopwatch()) {
            $this->stopwatch->stop($uri);
        }
    }

    /**
     * Logs error message on failure & stops stopwatch profiler
     *
     * @param string $id
     * @param string $method
     * @param string $uri
     * @param ResponseInterface $response
     */
    public function logFailedResponse(string $id, string $method, string $uri, ResponseInterface $response)
    {
        if ($this->hasLogger()) {
            $context = [['HTTP status code'] => $response->getStatusCode()];
            $context = array_merge($context, $response->getHeaders());
            $this->logger->error(LOG_MESSAGE_PREFIX . sprintf('Failed %s request to %s', $method, $uri), $context);
        }
        if ($this->hasStopwatch()) {
            $this->stopwatch->stop($uri);
        }
    }


}
