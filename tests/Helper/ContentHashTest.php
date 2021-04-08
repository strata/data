<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Helper\ContentHasher;

final class ContentHashTest extends TestCase
{

    protected $contentExamples = [
      "Some words.",
      "Some words that are different to the ones that went before.",
      "some words that are different to the ones that went before.",
      "Some words.",
      "And now for something completely different.",
    ];

    protected $arrayExamples = [
        [1, 2, 3, 4],
        [1, 2, 3, 4],
        ['title' => 'Example', 'body' => 'test content'],
        ['title' => 'Example', 'body' => 'test content'],
        ['title' => 'example', 'body' => 'test content'],
    ];

    public function testMultipleValidExamples()
    {
        foreach ($this->contentExamples as $contentExample) {
            $this->assertFalse(ContentHasher::hasContentChanged(ContentHasher::hash($contentExample), $contentExample));
        }
    }

    public function testContentHash()
    {
        $hashes = [];

        foreach ($this->contentExamples as $contentExample) {
            $hashes[] = ContentHasher::hash($contentExample);
        }

        $this->assertFalse(ContentHasher::hasContentChanged($hashes[0], $this->contentExamples[0]));
        $this->assertTrue(ContentHasher::hasContentChanged($hashes[1], $this->contentExamples[2]));
        $this->assertFalse(ContentHasher::hasContentChanged($hashes[0], $this->contentExamples[3]));
        $this->assertTrue(ContentHasher::hasContentChanged($hashes[3], $this->contentExamples[4]));
    }

    public function testFileHash()
    {
        $hash1 = ContentHasher::hash(file_get_contents(__DIR__ . '/content/test1.md'));
        $hash2 = ContentHasher::hash(file_get_contents(__DIR__ . '/content/test2.md'));
        $hash3 = ContentHasher::hash(file_get_contents(__DIR__ . '/content/test1.md'));

        $this->assertFalse($hash1 == $hash2);
        $this->assertTrue($hash1 == $hash3);
    }

    public function testInvalidType()
    {
        $this->expectException('InvalidArgumentException');
        ContentHasher::hash(new \DateTime());
    }

    public function testArray()
    {
        $this->assertFalse(ContentHasher::hasContentChanged(ContentHasher::hash($this->arrayExamples[0]), $this->arrayExamples[1]));
        $this->assertFalse(ContentHasher::hasContentChanged(ContentHasher::hash($this->arrayExamples[2]), $this->arrayExamples[3]));
        $this->assertTrue(ContentHasher::hasContentChanged(ContentHasher::hash($this->arrayExamples[2]), $this->arrayExamples[4]));
    }

}
