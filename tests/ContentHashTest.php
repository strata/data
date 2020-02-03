<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\ContentHash;
use Strata\Data\Exception\InvalidHashAlgorithm;

final class ContentHashTest extends TestCase
{

    public function testContentHash()
    {
        $contentHash = new ContentHash();
        foreach ($this->getContent() as $key => $val) {
            $contentHash->add($key, $val);
        }

        $this->assertFalse($contentHash->isChanged(0, "Some words."));
        $this->assertFalse($contentHash->isChanged(1, "Some words that are different to the ones that went before."));
        $this->assertFalse($contentHash->isChanged(4, "And now for something completely different."));
        $this->assertTrue($contentHash->isChanged(4, " And now for something completely different. "));
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidHashAlgorithm::class);
        $contentHash = new ContentHash('fictionalHash123');
    }

    public function testDifferentHash()
    {
        $contentHash = new ContentHash('md5');
        foreach ($this->getContent() as $key => $val) {
            $contentHash->add($key, $val);
        }

        $this->assertFalse($contentHash->isChanged(0, "Some words."));
        $this->assertFalse($contentHash->isChanged(1, "Some words that are different to the ones that went before."));
    }

    protected function getContent(): array
    {
        return [
            "Some words.",
            "Some words that are different to the ones that went before.",
            "More words that are different again.",
            "Another string slightly longer than before.",
            "And now for something completely different.",
        ];
    }

}
