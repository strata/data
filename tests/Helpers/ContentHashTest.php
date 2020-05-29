<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\InvalidHashAlgorithm;
use Strata\Data\Exception\InvalidHashIdentifier;
use Strata\Data\Helpers\ContentHasher;

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
      "More words that are different again.",
      "Another string slightly longer than before.",
      "And now for something completely different.",
    ];

    public function testMultpleValidExamples()
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
        $this->assertTrue($this->contentHasher->hasContentChanged($hashes[1], 'A different string to the original.'));
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidHashAlgorithm::class);
        $content = new ContentHasher('fictionalHash123');
    }


    public function testDifferentHash()
    {
        $this->contentHasher = new ContentHasher('md5');
        foreach ($this->contentExamples as $contentExample) {
            $hashes[] = $this->contentHasher->hash($contentExample);
        }

        $this->assertFalse($this->contentHasher->hasContentChanged($hashes[0], $this->contentExamples[0]));
        $this->assertTrue($this->contentHasher->hasContentChanged($hashes[1], 'A different string to the original.'));
    }
}
