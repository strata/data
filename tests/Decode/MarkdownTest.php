<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\Markdown;
use Strata\Data\Decode\MarkdownExtra;

final class MarkdownTest extends TestCase
{
    protected $markdown = <<<EOD
## Hello     {#header1}

World
EOD;

    public function testMarkdown()
    {
        $decoder = new Markdown();
        $html = $decoder->decode($this->markdown);
        $this->assertStringContainsString('<h2>Hello', $html);

        $html = $decoder->decode('test string * string');
        $this->assertEquals('<p>test string * string</p>', $html);
    }

    public function testMarkdownExtra()
    {
        $decoder = new MarkdownExtra();
        $html = $decoder->decode($this->markdown);
        $this->assertStringContainsString('<h2 id="header1">Hello', $html);
    }
}
