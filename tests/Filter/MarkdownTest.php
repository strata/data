<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\UnsupportedMethodException;
use Strata\Data\Filter\Markdown;

final class MarkdownTest extends TestCase
{
    protected $markdown = <<<EOD
## Hello     {#header1}

World
EOD;

    public function testMarkdown()
    {
        $filter = new Markdown();

        $html = $filter->filter($this->markdown);
        $this->assertStringContainsString('<h2>Hello', $html);
    }

    public function testMarkdownExtra()
    {
        $filter = new Markdown(Markdown::MARKDOWN_EXTRA);

        $html = $filter->filter($this->markdown);
        $this->assertStringContainsString('<h2 id="header1">Hello', $html);
    }

}
