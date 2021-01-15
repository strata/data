<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Data\Item;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Traits\FlagsTrait;
use Parsedown;
use ParsedownExtra;

/**
 * MarkdownDecoder decoder
 *
 * Supports MarkdownDecoder Extra
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class MarkdownDecoder implements DecoderInterface
{
    use FlagsTrait;

    const MARKDOWN_EXTRA = 1;

    /**
     * Filter markdown string to HTML
     * @param string $data
     * @param Item $item
     * @return string
     */
    public function decode(string $data, Item $item): string
    {
        if ($this->flagEnabled(self::MARKDOWN_EXTRA)) {
            $parsedown = new ParsedownExtra();
        } else {
            $parsedown = new Parsedown();
        }

        return $parsedown->text($data);
    }

}
