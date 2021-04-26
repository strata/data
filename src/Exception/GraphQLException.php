<?php

declare(strict_types=1);

namespace Strata\Data\Exception;

class GraphQLException extends HttpException
{
    /**
     * Return errors formatted as a string
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
