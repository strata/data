<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\DecoderStrategy;
use Strata\Data\Decode\Json;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Model\Response;
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
        return ContentHasher::hash($method . ' ' . $uri);
    }

    /**
     * Return decoder to decode responses
     *
     * @return ?DecoderStrategy Decoder
     */
    public function getDefaultDecoder(): ?DecoderInterface
    {
        return new Json();
    }

    /**
     * Populate response with data content
     *
     * By default, populate array data into content
     * Expand on this in child classes
     *
     * @param Response $response
     * @param ResponseInterface $httpResponse
     * @return void
     */
    public function populateResponse(Response $response, ResponseInterface $httpResponse): void
    {
        $data = $this->decode($httpResponse->getContent());
        //$item = $response->add($response->getRequestId(), $data);

        $response->setRawContent($data);
    }

    /**
     * Check whether a response is failed and if so, throw a FailedRequestException
     *
     * Nothing to do since RestAPI responses fail on non-200 status code which is dealt with by HTTPClient
     *
     * @param $response
     * @return void
     * @throws FailedRequestException
     */
    public function throwExceptionOnFailedRequest($response): void
    {
    }
}
