<?php
declare(strict_types=1);

namespace Strata\Data\Api;

use Strata\Data\Exception\InvalidJsonResponse;
use Strata\Data\Exception\InvalidUriPatternException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Strata\Data\Permissions;
use Strata\Data\Exception\NotFoundException;
use Strata\Data\Exception\PermissionException;
use Strata\Data\Exception\FailedRequestException;
use Strata\Data\Exception\ApiException;

/**
 * Simple abstract class for communicating with a RESTful API
 *
 * @package Strata\Frontend
 */
abstract class RestApiAbstract implements ApiInterface
{
    /**
     * Default user agent string to use with requests
     *
     * @var string
     */
    const USER_AGENT = 'Strata (https://github.com/strata/data)';

    /**
     * URI format when getting one item from the API
     *
     * The two strings in this URI pattern are:
     * - URI
     * - Identifier
     *
     * @var string
     */
    protected $uriGetOne = '%s/%s';

    /**
     * Keep track of total number of requests per page load
     *
     * @var int
     */
    protected $totalRequests = 0;

    /**
     * API base URI
     *
     * @var string
     */
    protected $baseUri;

    /**
     * HTTP client to access the API
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * Permissions for accessing the API
     *
     * Used to protect against accidental misuse
     *
     * @var Permissions
     */
    protected $permissions;

    /**
     * Expected response code from requests which indicate a success
     *
     * @var bool
     */
    protected $expectedResponseCode = 200;

    /**
     * Is the request successful?
     *
     * @var bool
     */
    protected $success = false;

    /**
     * Whether to throw an exception on a failed HTTP request
     *
     * @var bool
     */
    protected $throwOnFailedRequest = true;

    /**
     * Array of response error codes to ignore and not throw an exception for
     *
     * @var array
     */
    protected $ignoreErrorCodes = [401];

    /**
     * Constructor
     *
     * @param string $baseUri API base URI
     * @param Permissions $permissions (if not passed, default = read-only)
     */
    public function __construct(string $baseUri, Permissions $permissions = null)
    {
        $this->setBaseUri($baseUri);

        if ($permissions instanceof Permissions) {
            $this->setPermissions($permissions);

        } else {
            $this->setPermissions(new Permissions(Permissions::READ));
        }
    }

    /**
     * Setup and return the HTTP client to communicate with the API
     *
     * @return HttpClientInterface
     */
    abstract public function setupHttpClient(): HttpClientInterface;

    /**
     * Set the API base URI
     *
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * Return API base URI
     *
     * @return string
     * @throws ApiException
     */
    public function getBaseUri(): string
    {
        if (empty($this->baseUri)) {
            throw new ApiException(sprintf('API base URL not set, please set via %s::setBaseUri()', get_class($this)));
        }

        return $this->baseUri;
    }

    /**
     * Set the get one URI to use when running getOne()
     *
     * @param string $pattern
     * @throws InvalidUriPatternException
     */
    public function getOneUri(string $pattern)
    {
        // Require two instances of %s
        if (substr_count($pattern,'%s') !== 2) {
            throw new InvalidUriPatternException('URI pattern must include two instances of %s, passed string: ' . $pattern);
        }

        $this->uriGetOne = $pattern;
    }

    /**
     * Return the user agent string to use with HTTP requests
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return self::USER_AGENT;
    }

    /**
     * Set expected response code for successful requests
     *
     * @param integer $code
     * @throws \Exception
     */
    public function expectedResponseCode(int $code)
    {
        $this->expectedResponseCode = $code;
    }

    /**
     * Whether the current request is considered successful?
     *
     * This returns true if the request meets the expected response code
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set an error code to be ignored in the response (and not throw an exception)
     *
     * @param int $code
     */
    public function ignoreErrorCode(int $code)
    {
        $this->ignoreErrorCodes[] = $code;
    }

    /**
     * Whether to throw an exception on a failed request
     *
     * Defaults to true
     *
     * @param bool $throw
     */
    public function throwOnFailedRequest($throw = true)
    {
        $this->throwOnFailedRequest = $throw;
    }

    /**
     * Set permissions
     *
     * @param Permissions $permissions
     */
    public function setPermissions(Permissions $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Check whether you are allowed to perform the following action on the API
     *
     * @param int $action
     * @throws PermissionException
     */
    public function checkPermission(int $action)
    {
        if (!$this->permissions->isAllowed($action)) {
            $message = sprintf('Permission not allowed error. Requested permission: %s, Allowed permissions: %s', $this->permissions->getName($action), $this->permissions->__toString());
            throw new PermissionException($message);
        }
    }

    /**
     * Check whether you are allowed to perform a READ operation on the API
     *
     * @throws PermissionException
     */
    public function permissionRead()
    {
        $this->checkPermission(Permissions::READ);
    }

    /**
     * Check whether you are allowed to perform a WRITE operation on the API
     *
     * @throws PermissionException
     */
    public function permissionWrite()
    {
        $this->checkPermission(Permissions::WRITE);
    }

    /**
     * Check whether you are allowed to perform a DELETE operation on the API
     *
     * @throws PermissionException
     */
    public function permissionDelete()
    {
        $this->checkPermission(Permissions::DELETE);
    }

    /**
     * Set HTTP client
     *
     * @param HttpClient $client
     */
    public function setClient(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Return the HTTP client
     *
     * Connects to the HTTP client via RestApiAbstract::setupHttpClient if it does not already exist
     *
     * @return HttpClient
     */
    public function getClient(): HttpClientInterface
    {
        if ($this->client instanceof HttpClientInterface) {
            return $this->client;
        }

        $this->setClient($this->setupHttpClient());
        return $this->client;
    }

    /**
     * Make a request to the API
     *
     * @param string $method Request type
     * @param string $uri
     * @param array $options Array of options
     * @return ResponseInterface
     * @throws FailedRequestException
     * @throws NotFoundException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $uri, array $options): ResponseInterface
    {
        /*
        if ($this->hasLogger()) {
            $this->getLogger()->info(sprintf('REST API request: %s %s (options: %s)', $method, $uri, $this->formatArray($options)));
        }
        */

        $response = $this->getClient()->request($method, $uri, $options);
        $this->totalRequests++;

        // Request succeeded
        if ($response->getStatusCode() == $this->expectedResponseCode) {
            $this->success = true;
            return $response;
        }

        // Errors suppressed, mark request as failed and return it
        if (!$this->throwOnFailedRequest) {
            $this->success = false;
            return $response;
        }

        // Request failed
        $message = sprintf('Failed HTTP response. Expected: %s, Actual: %s, Error: %s', $this->expectedResponseCode, $response->getStatusCode(), $response->getReasonPhrase());

        if (substr((string) $response->getStatusCode(), 0, 1) === '4') {
            throw new NotFoundException($message, $response->getStatusCode());
        } else {
            throw new FailedRequestException($message, $response->getStatusCode());
        }
    }

    /**
     * Make a GET request to the API
     *
     * @param string $uri URI relative to base URI
     * @param array $queryParams Array of query params to send with GET request
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     */
    public function get(string $uri, array $queryParams = [], array $options = []): ResponseInterface
    {
        if (isset($options['query'])) {
            $options['query'] = array_merge($queryParams, $options['query']);
        } else {
            $options['query'] = $queryParams;
        }

        return $this->request('GET', $uri, $options);
    }

    /**
     * Make a POST request to the API
     *
     * @param string $uri URI relative to base URI
     * @param array $postData Array of data to send with POST request
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     */
    public function post(string $uri, array $postData = [], array $options): ResponseInterface
    {
        if (isset($options['body']) && is_array($options['body'])) {
            $options['body'] = array_merge($postData, $options['body']);
        } else {
            $options['body'] = $postData;
        }

        return $this->request('POST', $uri, $options);
    }

    /**
     * Make a HEAD request to the API
     *
     * @param string $uri URI relative to base URI
     * @param array $options
     * @return ResponseInterface
     * @throws FailedRequestException
     */
    public function head(string $uri, array $options): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    /**
     * Return a header from a response
     *
     * Returns one single header value, or an array of values if there are more than one
     *
     * @param ResponseInterface $response Response to extract header from
     * @param $header Header to return (converted to lower case)
     * @return mixed|string|string[]
     */
    public function getHeader(ResponseInterface $response, string $header)
    {
        $header = strtolower($header);

        $headers = $response->getHeaders();
        if (isset($headers[$header])) {
            if (count($headers[$header]) == 1) {
                return $headers[$header][0];
            } else {
                return $headers[$header];
            }
        }
    }

    /**
     * Return number of total requests made
     *
     * @return int
     */
    public function getTotalRequests(): int
    {
        return $this->totalRequests;
    }
}
