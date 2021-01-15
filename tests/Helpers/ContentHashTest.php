<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\InvalidHashAlgorithm;
use Strata\Data\Helper\ContentHasher;

final class ContentHashTest extends TestCase
{
    /** @var ContentHasher */
    protected $contentHasher;

    /**
     * Sets up the properties before the next test runs
     */
    protected function setUp(): void
    {
        $this->contentHasher = new ContentHasher();
    }

    protected $contentExamples = [
      "Some words.",
      "Some words that are different to the ones that went before.",
      "some words that are different to the ones that went before.",
      "Some words.",
      "And now for something completely different.",
    ];

    public function testMultipleValidExamples()
    {
        foreach ($this->contentExamples as $contentExample) {
            $this->assertFalse($this->contentHasher->hasContentChanged($this->contentHasher->hash($contentExample), $contentExample));
        }
    }

    public function testContentHash()
    {
        $hashes = [];

        foreach ($this->contentExamples as $contentExample) {
            $hashes[] = $this->contentHasher->hash($contentExample);
        }

        $this->assertFalse($this->contentHasher->hasContentChanged($hashes[0], $this->contentExamples[0]));
        $this->assertTrue($this->contentHasher->hasContentChanged($hashes[1], $this->contentExamples[2]));
        $this->assertFalse($this->contentHasher->hasContentChanged($hashes[0], $this->contentExamples[3]));
        $this->assertTrue($this->contentHasher->hasContentChanged($hashes[3], $this->contentExamples[4]));
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidHashAlgorithm::class);
        $content = new ContentHasher('fictionalHash123');
    }

    public function testDifferentHashAlgorithm()
    {
        $this->contentHasher = new ContentHasher('md5');
        foreach ($this->contentExamples as $contentExample) {
            $hashes[] = $this->contentHasher->hash($contentExample);
        }

        $this->assertFalse($this->contentHasher->hasContentChanged($hashes[0], $this->contentExamples[0]));
        $this->assertTrue($this->contentHasher->hasContentChanged($hashes[1], $this->contentExamples[2]));
    }

    public function testFileHash()
    {
        $hash1 = $this->contentHasher->hash(file_get_contents(__DIR__ . '/../Filesystem/files/test-1.md'));
        $hash2 = $this->contentHasher->hash(file_get_contents(__DIR__ . '/../Filesystem/files/test-2.md'));
        $hash3 = $this->contentHasher->hash(file_get_contents(__DIR__ . '/../Filesystem/files/test-1.md'));

        $this->assertFalse($hash1 == $hash2);
        $this->assertTrue($hash1 == $hash3);
    }

}
