<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Data\Item;
use Strata\Data\Exception\DecoderException;

/**
 * JSON decoder
 *
 * Returns data as an associative array to ensure the same data format for all types of JSON data
 * @package Strata\Data\Filter
 */
class JsonDecoder implements DecoderInterface
{

    /**
     * Decode JSON string to array
     *
     * @param string $data
     * @param Item $item
     * @return array
     * @throw \JsonException
     * @throws DecoderException
     */
    public function decode(string $data, Item $item): array
    {
        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DecoderException('Error parsing JSON response body: ' . json_last_error_msg(), 0, $e);
        }
        return $data;
    }

}
