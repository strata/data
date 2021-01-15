<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderStrategy;
use Strata\Data\Decode\JsonDecoder;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Response\HttpResponse;
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
     * Return a unique identifier safe to use for caching based on the request
     *
     * Method + URI + GET params
     *
     * @param $method
     * @param $uri
     * @param array $options
     * @return string Hash of the identifier
     */
    public function getRequestIdentifier($method, $uri, array $options = []): string
    {
        if (!empty($options['query'])) {
            $uri .= '?' . urlencode($options['query']);
        }
        return return ContentHasher::hash($method . ' ' . $uri);
    }

    /**
     * Setup decoder to use on body content of responses
     *
     * @return ?DecoderStrategy Decoder or null if body is not to be processed
     */
    public function setupDecoder(): ?DecoderStrategy
    {
        return new DecoderStrategy(new JsonDecoder());
    }

    /**
     * Is the response successful?
     *
     * @param ResponseInterface $response
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function isSuccessful(HttpResponse $response): bool
    {
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }


}
