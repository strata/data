<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Decode\FrontMatter;

final class YamlFrontMatterTest extends TestCase
{
    protected $text1 = <<<EOD
My test text is here
EOD;

    protected $text2 = <<<EOD
---
title: Valid example
layout: test2
---
My test text is here
EOD;

    protected $text3 = <<<EOD
--
title: Not valid
layout: test3
--
My test text is here
EOD;

    public function testFrontMatter()
    {
        $decoder = new FrontMatter();
        $data = $decoder->decode($this->text1);

        /**
         * PHPStan doesn't like testing dynamic properties returned via __get()
         * @see https://phpstan.org/blog/solving-phpstan-access-to-undefined-property
         */
        $this->assertNull($data->title); /* @phpstan-ignore-line */
        $this->assertEquals($this->text1, $data->body());

        $data = $decoder->decode($this->text2);
        $this->assertEquals('Valid example', $data->title); /* @phpstan-ignore-line */
        $this->assertEquals('test2', $data->layout); /* @phpstan-ignore-line */
        $this->assertEquals(PHP_EOL . $this->text1, $data->body());

        $data = $decoder->decode($this->text3);
        $this->assertNull($data->title); /* @phpstan-ignore-line */
        $this->assertEquals($this->text3, $data->body());
    }
}
