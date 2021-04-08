<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Laminas\Feed\Reader\Feed\FeedInterface;
use Laminas\Feed\Reader\Reader;
use Strata\Data\Exception\DecoderException;

/**
 * RSS decoder
 *
 * @package Strata\Data\Filter
 */
class Rss implements DecoderInterface
{
    /**
     * Decode RSS XML string to a feed object
     *
     * @see https://docs.laminas.dev/laminas-feed/reader/#retrieving-feed-information
     * @param string|object $data
     * @return FeedInterface
     * @throws DecoderException
     */
    public function decode($data): FeedInterface
    {
        $data = StringNormalizer::getString($data);
        return Reader::importString($data);
    }

}
