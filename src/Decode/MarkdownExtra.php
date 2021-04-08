<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use ParsedownExtra;

/**
 * Markdown decoder
 *
 * Supports Markdown Extra
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class MarkdownExtra implements DecoderInterface
{
    /**
     * Filter markdown string to HTML
     * @param string|object $data
     * @return string
     */
    public function decode($data): string
    {
        $data = StringNormalizer::getString($data);
        $parsedown = new ParsedownExtra();
        return $parsedown->text($data);
    }
}
