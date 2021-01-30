<?php
declare(strict_types=1);

namespace Strata\Data\Decoder;

interface DecoderInterface
{
    /**
     * Decode data
     *
     * @param string $data Data string to decode
     * @return mixed Decoded data
     */
    public static function decode(string $data);
}