<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Data\Item;

interface DecoderInterface
{
    /**
     * Decode data
     * @param string $data Data string to decode
     * @param Item $item Item object, in case we wish to set any metadata
     * @return mixed Decoded data (to set to Item content)
     */
    public function decode(string $data, Item $item);
}