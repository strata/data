<?php

declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\GraphQL as GraphQLDecoder;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\GraphQLException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Http\Response\CacheableResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQL extends Http
{
    /**
     * Default HTTP options when creating new HttpClient objects for this data provider
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @var array|array[]
     */
    protected array $defaultOptions = [
        'headers' => [
            'Content-Type' => 'application/json',
        ]
    ];

    protected array $cacheableMethods = ['GET', 'HEAD', 'POST'];

    private string $lastQuery;

    /**
     * Return default decoder to use to decode responses
     *
     * Returns data from 'data' property
     *
     * @return DecoderInterface
     */
    public function getDefaultDecoder(): DecoderInterface
    {
        if (null === $this->defaultDecoder) {
            $this->setDefaultDecoder(new GraphQLDecoder());
        }
        return $this->defaultDecoder;
    }

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * Hash generated from: URI + GET params + GraphQL query
     *
     * @param string $uri
     * @param array $context
     * @return string Hash of the identifier
     */
    public function getRequestIdentifier(string $uri, array $context = []): string
    {
        if (!empty($context['query'])) {
            $uri .= '?' . http_build_query($context['query']);
        }
        if (is_array($context['body'])) {
            return ContentHasher::hash($uri . ' ' . http_build_query($context['body']));
        } else {
            return ContentHasher::hash($uri . ' ' . (string) $context['body']);
        }
    }

    /**
     * Check whether a response is failed and if so, throw a Strata\Data\Exception\HttpException
     *
     * @param ResponseInterface $response
     * @return void
     * @throws GraphQLException
     */
    public function throwExceptionOnFailedRequest(ResponseInterface $response): void
    {
        // Throws an exception on HTTP error
        $content = $response->toArray();

        // OK response, errors not set
        if (!isset($content['errors']) || !is_array($content['errors'])) {
            return;
        }

        // Error response, errors set
        $requestId = $response->getInfo('user_data');
        $errorData = $content['errors'];

        try {
            $partialData = $this->decode($response);
        } catch (DecoderException $e) {
            $partialData = [];
        }

        // Exception message is generated from $errorData
        throw new GraphQLException(
            '',
            $this->requestTrace->getRequestUri($requestId),
            $this->requestTrace->getRequestMethod($requestId),
            $this->requestTrace->getRequestOptions($requestId),
            $response,
            $errorData,
            $partialData,
            null,
            $this->getLastQuery()
        );
    }

    /**
     * Return last GraphQL query
     *
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Set last GraphQL query
     *
     * @param string $lastQuery
     */
    public function setLastQuery(string $lastQuery): void
    {
        $this->lastQuery = $lastQuery;
    }

    /**
     * Ping a GraphQL API service to check it is online
     *
     * @return bool
     * @throws \JsonException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function ping(): bool
    {
        $response = $this->query('{ping}');
        $item = $this->decode($response);
        if (isset($item['ping']) && $item['ping'] == 'pong') {
            return true;
        }
        return false;
    }

    /**
     * Build GraphQL request from a JSON query and array of variables
     *
     * Uses the baseUri to send requests to
     * GraphQL queries are sent as POST requests with the JSON query in the body
     *
     * @param array $query GraphQL query
     * @param ?array $variables Array of variables to pass to GraphQL (key & value pairs)
     * @param ?string $operationName Operation name to execute (only required if query contains multiple operations)
     * @return ResponseInterface
     * @throws \JsonException on invalid query JSON string
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function query(string $query, ?array $variables = [], ?string $operationName = null): CacheableResponse
    {
        return $this->post('', $this->buildQuery($query, $variables, $operationName));
    }

    /**
     * Build and validate GraphQL JSON query
     *
     * @param string $query GraphQL query
     * @param ?array $variables Array of variables to pass to GraphQL (key & value pairs)
     * @param ?string $operationName Operation name to execute (only required if query contains multiple operations)
     * @return string
     * @throws \JsonException
     */
    public function buildQuery(string $query, ?array $variables = [], ?string $operationName = null): string
    {
        // Make sure GraphQL query is on one line so valid JSON
        $query = preg_replace('/\s+/', ' ', $query);
        $query = trim($query);
        $data = ['query' => $query];

        if (!empty($operationName)) {
            $data['operationName'] = $operationName;
        }
        if (!empty($variables)) {
            $data['variables'] = $variables;
        }

        $query = json_encode($data, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->setLastQuery($query);
        return $query;
    }
}
