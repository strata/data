<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

interface DecoderInterface
{
    /**
     * Decode data
     *
     * @param string|object $data Data string to decode
     * @return mixed Decoded data
     */
    public function decode($data);

    /**
     * Whether the decoder can decide this data
     *
     * @param $data
     * @return bool
     */
    //public function canDecode($data): bool;
}
