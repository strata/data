<?php
declare(strict_types=1);

namespace Strata\Data\Decode;

use Parsedown;

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
     * @param string $data
     * @return string
     */
    public function decode(string $data): string
    {
        $parsedown = new Parsedown();
        return $parsedown->text($data);
    }
}