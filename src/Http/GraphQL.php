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

class GraphQL extends HttpAbstract
{
    const JSON_ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT;

    private string $lastQuery;

    public function __construct(?string $baseUri = null)
    {
        if ($baseUri !== null) {
            $this->setBaseUri($baseUri);
        }
    }

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
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
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
     * Setup decoder to use on body content of responses
     *
     * @return ?DecoderStrategy Decoder or null if body is not to be processed
     */
    public function setupDecoder(): ?DecoderStrategy
    {
        return new DecoderStrategy(new JsonDecoder());
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
     * Is the response successful, detect GraphQL errors
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
            $data = $response->toArray();

            if (isset($data['errors']) && is_array($data['errors'])) {
                $response->setSuccess(false);
                $response->setErrorMessage($data['errors'][0]['message']);
                if (count($data['errors']) === 1) {
                    $response->setErrorData($data['errors'][0]);
                } else {
                    $response->setErrorData($data['errors']);
                }
                return false;
            }

            $response->setSuccess(true);
            return true;
        }

        $response->setSuccess(false);
        return false;
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
        $result = $this->query('{ping}');
        if ($result->isSuccess() && isset($result->toArray()['data']['ping']) && $result->toArray()['data']['ping'] === 'pong') {
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
     * @return HttpResponse
     * @throws \JsonException on invalid query JSON string
     * @throws \Strata\Data\Exception\BaseUriException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function query(string $query, ?array $variables = [], ?string $operationName = null): HttpResponse
    {
        return $this->request('POST', $this->getBaseUri(), ['body' => $this->buildQuery($query, $variables, $operationName)]);
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

        $query = json_encode($data, self::JSON_ENCODE_FLAGS);
        $this->setLastQuery($query);
        return $query;
    }

}
