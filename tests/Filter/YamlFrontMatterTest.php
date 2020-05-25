<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Filter\YamlFrontMatter;

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
        $filter = new YamlFrontMatter();

        $data = $filter->filter($this->text1);
        $this->assertEquals('My test text is here', trim($data));
        $this->assertNull($filter->getFrontMatter('title'));
        $this->assertEmpty($filter->getAllFrontMatter());

        $data = $filter->filter($this->text2);
        $this->assertEquals('My test text is here', trim($data));
        $this->assertEquals('Valid example', $filter->getFrontMatter('title'));
        $this->assertEquals('test2', $filter->getFrontMatter('layout'));
        $this->assertEquals(['title' => 'Valid example', 'layout' => 'test2'], $filter->getAllFrontMatter());

        $data = $filter->filter($this->text3);
        $this->assertNotEquals('My test text is here', trim($data));
        $this->assertEquals(trim($this->text3), trim($data));
        $this->assertNotEquals('Not valid', $filter->getFrontMatter('title'));
        $this->assertNull($filter->getFrontMatter('title'));
        $this->assertEmpty($filter->getAllFrontMatter());
    }

}
