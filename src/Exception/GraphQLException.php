<?php

declare(strict_types=1);

namespace Strata\Data\Exception;

use Symfony\Contracts\HttpClient\ResponseInterface;

class GraphQLException extends HttpException
{
    private string $lastQuery;

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
     * @param ?string $lastQuery
     * @param \Exception|null $previous
     */
    public function __construct(string $message, string $uri, string $method, array $options, ResponseInterface $response, array $errorData = [], array $responseData = [], \Exception $previous = null, ?string $lastQuery = null)
    {
        if (null !== $lastQuery) {
            $this->setLastQuery($lastQuery);
        }
        parent::__construct($message, $uri, $method, $options, $response, $errorData, $responseData, $previous);
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
     * Return exception message from error data
     *
     * @param array $errorData
     * @return string
     */
    public function getMessageFromErrorData(array $errorData): string
    {
        $errorContent = [];
        foreach ($errorData as $error) {
            if (!isset($error['message'])) {
                continue;
            }
            $content = $error['message'];
            if (isset($error['locations'])) {
                foreach ($error['locations'] as $location) {
                    $content = rtrim($content, '.');
                    $content .= sprintf(' on line %d, column %d', $location['line'], $location['column']);
                }
            }
            if (isset($error['path']) && is_array($error['path'])) {
                $content .= sprintf(', path: %s', implode(' > ', $error['path']));
            }
            $errorContent[] = $content;
        }
        if (count($errorContent) > 0) {
            return ' GraphQL errors: ' . implode('. ', $errorContent) . '.';
        }
        return '';
    }

    /**
     * Return errors formatted as an expanded multiline string
     *
     * @param array $errors
     * @return string
     * @see http://spec.graphql.org/draft/#sec-Errors
     */
    public function expandErrorValues(array $errors): string
    {
        $content = PHP_EOL;
        foreach ($errors as $error) {
            if (!isset($error['message'])) {
                continue;
            }
            $content .= 'GraphQL error: ' . $error['message'] . PHP_EOL;
            if (isset($error['locations'])) {
                foreach ($error['locations'] as $location) {
                    $content .= sprintf(self::INDENT . 'Location: line %d, column %d', $location['line'], $location['column']) . PHP_EOL;
                }
            }
            if (isset($error['path']) && is_array($error['path'])) {
                $content .= self::INDENT . sprintf('Path: %s', implode(' > ', $error['path']));
            }
            if (isset($error['extensions']) && is_array($error['extensions'])) {
                $content .= self::INDENT . 'Extensions: ' . $this->expandArrayValues($error['extensions'], self::INDENT . self::INDENT);
            }
        }
        return $content . PHP_EOL;
    }
}
