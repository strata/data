<?php
declare(strict_types=1);

namespace Strata\Data\Filter;

use Spatie\YamlFrontMatter\YamlFrontMatter as SpatieFrontMatter;

/**
 * YAML Front matter filter
 *
 * Front matter allows you to add variables at the top of a text file, in YAML format.
 *
 * E.g.
 * ---
 * name: value
 * ---
 *
 * The filter method returns the data string body after the front matter has been parsed out
 * To get the front matter variables, use YamlFrontMatter::getFrontMatter('name') or YamlFrontMatter::getAllFrontMatter()
 *
 * @see https://michelf.ca/projects/php-markdown/extra/
 * @package Strata\Data\Filter
 */
class YamlFrontMatter implements FilterInterface
{
    protected $frontMatter = [];

    public function filter($data): string
    {
        $frontMatter = SpatieFrontMatter::parse($data);
        $this->frontMatter = $frontMatter->matter();

        return $frontMatter->body();
    }

    /**
     * Return front matter variable
     * @param string $name
     * @return mixed
     */
    public function getFrontMatter(string $name = null)
    {
        if (isset($this->frontMatter[$name])) {
            return $this->frontMatter[$name];
        }
        return null;
    }

    /**
     * Return array of all front matter
     * @return array
     */
    public function getAllFrontMatter(): array
    {
        return $this->frontMatter;
    }

}