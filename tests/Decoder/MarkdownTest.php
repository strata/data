<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decoder\Markdown;
use Strata\Data\Decoder\MarkdownExtra;

final class MarkdownTest extends TestCase
{
    protected $markdown = <<<EOD
## Hello     {#header1}

World
EOD;

    public function testMarkdown()
    {
        $html = Markdown::decode($this->markdown);
        $this->assertStringContainsString('<h2>Hello', $html);

        $html = Markdown::decode('test string * string');
        $this->assertEquals('<p>test string * string</p>', $html);
    }

    public function testMarkdownExtra()
    {
        $html = MarkdownExtra::decode($this->markdown);
        $this->assertStringContainsString('<h2 id="header1">Hello', $html);
    }

}
