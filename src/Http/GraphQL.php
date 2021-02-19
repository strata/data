<?php
declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\DecoderStrategy;
use Strata\Data\Decode\Json;
use Strata\Data\Exception\FailedApiRequestException;
use Strata\Data\Exception\FailedGraphQLException;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Helper\ContentHasher;
use Strata\Data\Model\Response;
use Strata\Data\Traits\AuthTokenTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQL extends HttpAbstract
{
    private string $lastQuery;

    /**
     * Setup HTTP client
     *
     * @see https://symfony.com/doc/current/reference/configuration/framework.html#reference-http-client
     * @return HttpClientInterface
     */
    public function setupHttpClient(): HttpClientInterface
    {
        $options = [
            'base_uri' => $this->getBaseUri(),
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];

        return HttpClient::create($options);
    }

    /**
     * Return a unique identifier safe to use for caching based on the request
     *
     * Method + URI + GET params + GraphQL query
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
        return ContentHasher::hash($method . ' ' . $uri . ' ' . $options['body']);
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
     * Populates contents of 'data' response property, or an empty array if this is missing
     *
     * @param Response $response
     * @param ResponseInterface $httpResponse
     * @return void
     */
    public function populateResponse(Response $response, ResponseInterface $httpResponse): void
    {
        $httpResponse = $this->decode($httpResponse->getContent());

        // Add one item since cannot parse GraphQL response
        $data = [];
        if (isset($httpResponse['data']) && is_array($httpResponse['data'])) {
            $data = $httpResponse['data'];
        }
        $item = $response->add($response->getRequestId(), $data);

        // Add any GraphQL errors
        if (isset($httpResponse['errors']) && is_array($httpResponse['errors'])) {
            $response->setMeta('errors', $httpResponse['errors']);
        }
    }

    /**
     * Check whether a response is failed and if so, throw a FailedRequestException
     *
     * @param Response $response
     * @return void
     * @throws FailedRequestException
     */
    public function throwExceptionOnFailedRequest(Response $response): void
    {
        $errors = $response->getMeta('errors');

        if ($errors !== null && is_array($errors)) {
            $partialData = [];
            if (!empty($response->getItem()->getContent())) {
                $partialData = $response->getItem()->getContent();
            }
            throw new FailedGraphQLException(sprintf('GraphQL query failed: %s', $errors[0]['message']), $errors, $partialData);
        }
    }

    /**
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * @param string $lastQuery
     */
    public function setLastQuery(string $lastQuery): void
    {
        $this->lastQuery = $lastQuery;
    }

    /**
     * Ping a GraphQL API service to check it is online
     *
     * Sends request to baseUri
     *
     * @return bool
     * @throws \JsonException
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function ping(): bool
    {
        $response = $this->query('{ping}');
        $item = $response->getItem();
        if ($item['ping'] === 'pong') {
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
     * @return Response
     * @throws \JsonException on invalid query JSON string
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function query(string $query, ?array $variables = [], ?string $operationName = null): Response
    {
        return $this->request('POST', '', ['body' => $this->buildQuery($query, $variables, $operationName)]);
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
