<?php
declare(strict_types=1);

namespace Strata\Data\Filter;

use Strata\Data\Traits\FlagsTrait;
use Parsedown;
use ParsedownExtra;

/**
 * Markdown filter
 *
 * Supports Markdown Extra
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class Markdown implements FilterInterface
{
    use FlagsTrait;

    const MARKDOWN_EXTRA = 1;

    /**
     * Filter markdown string to HTML
     * @param string $data
     * @return string
     */
    public function filter($data): string
    {
        if ($this->flagEnabled(self::MARKDOWN_EXTRA)) {
            $parsedown = new ParsedownExtra();
        } else {
            $parsedown = new Parsedown();
        }

        return $parsedown->text($data);
    }

}
