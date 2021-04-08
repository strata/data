<?php

declare(strict_types=1);

namespace Strata\Data\Decode;

use Spatie\YamlFrontMatter\Document;
use Strata\Data\Data_DELETE\Item;
use Spatie\YamlFrontMatter\YamlFrontMatter as SpatieFrontMatter;

/**
 * YAML Front matter decoder
 *
 * Front matter allows you to add variables at the top of a text file, in YAML format.
 *
 * E.g.
 * ---
 * title: example
 * ---
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class FrontMatter implements DecoderInterface
{
    /**
     * Parse content and return
     *
     * Return body content (with front matter stripped out):
     * $item->body()
     *
     * Return front matter
     * $item->title
     *
     * or:
     * $item->matter('title')
     *
     * @param string|object $data
     * @return Document Array of front matter or empty array on failure
     */
    public function decode($data): Document
    {
        $data = StringNormalizer::getString($data);
        return SpatieFrontMatter::parse($data);
    }
}
