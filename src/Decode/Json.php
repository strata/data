<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Exception\DecoderException;

/**
 * JSON decoder
 *
 * Returns data as an associative array to ensure the same data format for all types of JSON data
 * @package Strata\Data\Filter
 */
class Json implements DecoderInterface
{
    /**
     * Decode JSON string to array
     *
     * @param string|object $data
     * @return array
     * @throws DecoderException
     */
    public function decode($data): array
    {
        $data = StringNormalizer::getString($data);

        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            throw new DecoderException('Error parsing JSON response body: ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($data)) {
            throw new DecoderException('JSON response body is expected to be an array');
        }

        return $data;
    }
}
