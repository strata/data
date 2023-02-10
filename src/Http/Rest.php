<?php

declare(strict_types=1);

namespace Strata\Data\Http;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Decode\Json;

class Rest extends Http
{
    /**
     * Return default decoder to use to decode responses
     *
     * @return DecoderInterface
     */
    public function getDefaultDecoder(): DecoderInterface
    {
        if (null === $this->defaultDecoder) {
            $this->setDefaultDecoder(new Json());
        }
        return $this->defaultDecoder;
    }
}
