<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

interface DecoderInterface
{
    /**
     * Decode data
     *
     * @param string $data Data string to decode
     * @return mixed Decoded data
     */
    public function decode(string $data);
}
