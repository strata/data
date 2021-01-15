<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

use Strata\Data\Data\Item;
use Strata\Data\Exception\DecoderException;
use Spatie\YamlFrontMatter\YamlFrontMatter as SpatieFrontMatter;

/**
 * YAML Front matter decoder
 *
 * Front matter allows you to add variables at the top of a text file, in YAML format.
 *
 * E.g.
 * ---
 * name: value
 * ---
 *
 * The filter method returns the data string body after the front matter has been parsed out
 * To get the front matter variables, use $item->getMeta('name') or $item->getAllMeta()
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class YamlFrontMatterDecoder implements DecoderInterface
{
    /**
     * Filter out front matter and return body content, set front matter as metadata
     * @param string $data
     * @param Item $item
     * @return string
     */
    public function decode(string $data, Item $item): string
    {
        $frontMatter = SpatieFrontMatter::parse($data);
        $item->setMetaFromArray($frontMatter->matter());

        return $frontMatter->body();
    }
}
