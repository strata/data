<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Exception\DecoderException;

/**
 * GraphQL decoder
 *
 * Returns data as an associative array to ensure the same data format for all types of JSON data
 */
class GraphQL extends Json
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
        $data = parent::decode($data);

        if (!isset($data['data'])) {
            throw new DecoderException('Data property not present in GraphQL response');
        }

        $data = $data['data'];
        return $data;
    }
}
