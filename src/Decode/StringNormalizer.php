<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Exception\DecoderException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class StringNormalizer
{
    /**
     * Take a string or object, and return a string representing the content
     *
     * @param mixed $data
     * @return string
     * @throws DecoderException
     */
    public static function getString($data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_object($data)) {
            if ($data instanceof ResponseInterface) {
                return $data->getContent();
            }

            if (method_exists($data, '__toString')) {
                return $data->__toString();
            }
            throw new DecoderException(sprintf('Cannot convert object of type "%s" into a string', get_class($data)));
        }
        throw new DecoderException(sprintf('Cannot convert %s into a string', gettype($data)));
    }
}
