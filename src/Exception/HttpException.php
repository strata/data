<?php

declare(strict_types=1);

namespace Strata\Data\Exception;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpException extends \Exception
{
    const INDENT = ' ';
    private string $requestUri;
    private string $requestMethod;
    private array $requestOptions;
    private ?string $requestBody = null;
    private array $responseErrorData;
    private array $responseData;
    private ResponseInterface $response;

    /**
     * HttpException
     *
     * Outputs key HTTP request and response data with exception, data can also be accessed via getters
     *
     * @param $message
     * @param string $uri
     * @param string $method
     * @param array $options
     * @param ResponseInterface $response
     * @param array $errorData
     * @param array $responseData
     * @param \Exception|null $previous
     */
    public function __construct(string $message, string $uri, string $method, array $options, ResponseInterface $response, array $errorData = [], array $responseData = [], \Exception $previous = null)
    {
        $this->requestUri = $uri;
        $this->requestMethod = $method;
        $this->requestOptions = $options;
        $this->response = $response;
        $this->responseErrorData = $errorData;
        $this->responseData = $responseData;

        // Append info on HTTP request and response
        $httpInfo = 'Request: ' . $method . ' ' . $uri . PHP_EOL;
        foreach ($options as $name => $values) {
            if (in_array($name, ['body', 'user_data'])) {
                continue;
            }
            switch ($name) {
                case 'query':
                    $httpInfo .= 'Request query params: ' . $this->expandArrayValues($values);
                    break;
                case 'headers':
                    $httpInfo .= 'Request headers: ' . $this->expandArrayValues($values);
                    break;
                case 'extra':
                    $httpInfo .= 'Request extra: ' . $this->expandArrayValues($values);
                    break;
                default:
                    $httpInfo .= $name . ': ' . $values . PHP_EOL;
            }
        }
        if (isset($options['body'])) {
            $httpInfo .= 'Request body: ';
            $httpInfo .= print_r($options['body'], true) . PHP_EOL;
        }
        $httpInfo .= PHP_EOL;
        try {
            $httpInfo .= 'Response status: ' . $response->getStatusCode() . PHP_EOL;
            $headers = $response->getHeaders();
            if (!empty($errorData)) {
                $httpInfo .= 'Response error data: ' . $this->expandErrorValues($errorData);
            }
            if (!empty($headers)) {
                $httpInfo .= 'Response headers: ' . $this->expandArrayValues($response->getHeaders());
            }
        } catch (HttpExceptionInterface $e) {
            // continue
        }
        $message .= PHP_EOL . PHP_EOL . $httpInfo;

        parent::__construct($message, 0, $previous);
    }

    /**
     * Expand array values into key: value pairs
     * @param array $array
     * @param string $indent
     * @return string
     */
    public function expandArrayValues(array $array, $indent = self::INDENT): string
    {
        $content = '';
        array_walk_recursive($array, function ($value, $key) use (&$content, $indent) {
            static $x;
            if ($x > 0) {
                $content .= $indent;
            }
            $content .= $key . ': ' . $value . PHP_EOL;
            $x++;
        });
        return $content;
    }

    /**
     * Return errors formatted as a string
     * @param array $errors
     * @return string
     */
    public function expandErrorValues(array $errors): string
    {
        return print_r($errors, true);
    }

    /**
     * Return request URI
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * Return request method
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Return request options
     * @return array
     */
    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

    /**
     * Return request body
     * @return string|null
     */
    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    /**
     * Return any error data sent in the response
     * @return array
     */
    public function getResponseErrorData(): array
    {
        return $this->responseErrorData;
    }

    /**
     * Return any partial response data / content sent in the response
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
