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
     * @param string $data
     * @return array
     * @throws DecoderException
     */
    public function decode(string $data): array
    {
        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DecoderException('Error parsing JSON response body: ' . $e->getMessage(), 0, $e);
        }
        return $data;
    }
}
