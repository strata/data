<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use League\CommonMark\CommonMarkConverter;

/**
 * Markdown decoder
 *
 * Supports Markdown Extra
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class Markdown implements DecoderInterface
{
    /**
     * Filter markdown string to HTML
     * @param string|object $data
     * @return string
     */
    public function decode($data): string
    {
        $data = StringNormalizer::getString($data);
        $converter = new CommonMarkConverter();
        return $converter->convert($data)->getContent();
    }
}
