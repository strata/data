<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\Json;
use Strata\Data\Helper\ContentHasher;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RestApi extends HttpAbstract
{
    /**
     * Setup HTTP client
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @return HttpClientInterface
     */
    public function setupHttpClient(): HttpClientInterface
    {
        return HttpClient::create([
            'base_uri' => $this->getBaseUri(),
        ]);
    }

    /**
     * Return default decoder to use to decode responses
     *
     * @return DecoderInterface
     */
    public function getDefaultDecoder(): DecoderInterface
    {
        return new Json();
    }

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * @param $method
     * @param $uri
     * @param array $context
     * @return string Unique identifier for this request
     */
    public function getRequestIdentifier(string $uri, array $context = []): string
    {
        if (!empty($options['query'])) {
            $uri .= '?' . urlencode($options['query']);
        }
        return ContentHasher::hash($uri);
    }

    /**
     * Check whether a response is failed and if so, throw an exception
     *
     * @param ResponseInterface $response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function throwExceptionOnFailedRequest(ResponseInterface $response): void
    {
        // Will throw exception on error since checks for 200 status
        $response->getHeaders();
    }
}
