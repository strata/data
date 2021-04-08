<?php

declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\GraphQL as GraphQLDecoder;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\FailedGraphQLException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Version;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQL extends Http
{
    /**
     * Default HTTP options when creating new HttpClient objects for this data provider
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @var array|array[]
     */
    const DEFAULT_OPTIONS = [
        'headers' => [
            'User-Agent' => Version::USER_AGENT,
            'Content-Type' => 'application/json',
        ]
    ];

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
        if (!empty($options['query'])) {
            $uri .= '?' . urlencode($options['query']);
        }
        return ContentHasher::hash($uri . ' ' . $context['body']['body']);
    }

    /**
     * Check whether a response is failed and if so, throw a FailedRequestException
     *
     * @param ResponseInterface $response
     * @return void
     * @throws FailedGraphQLException
     */
    public function throwExceptionOnFailedRequest(ResponseInterface $response): void
    {
        // Throws an exception on HTTP error
        $content = $response->toArray();

        // GraphQL errors are returned in 'errors' property
        if (!isset($content['errors']) || !is_array($content['errors'])) {
            return;
        }
        $errors = $content['errors'];

        try {
            $partialData = $this->decode($response);
        } catch (DecoderException $e) {
            $partialData = [];
        }
        throw new FailedGraphQLException(sprintf('GraphQL query failed: %s', $errors[0]['message']), $errors, $partialData);
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
    public function query(string $query, ?array $variables = [], ?string $operationName = null): ResponseInterface
    {
        return $this->post('', ['body' => $this->buildQuery($query, $variables, $operationName)]);
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

        $query = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $this->setLastQuery($query);
        return $query;
    }
}
