<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\Markdown;
use Strata\Data\Decode\MarkdownExtra;

final class MarkdownTest extends TestCase
{
    protected $markdown = <<<EOD
## Hello

World
EOD;

    public function testMarkdown()
    {
        $decoder = new Markdown();
        $html = $decoder->decode($this->markdown);
        $this->assertStringContainsString('<h2>Hello', $html);

        $html = $decoder->decode('test string * string');
        $this->assertStringContainsString('<p>test string * string</p>', $html);
    }
}
