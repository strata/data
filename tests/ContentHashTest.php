<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\ContentHash;
use Strata\Data\Exception\InvalidHashAlgorithm;
use Strata\Data\Exception\InvalidHashIdentifier;

final class ContentHashTest extends TestCase
{

    public function testContentHash()
    {
        $content = new ContentHash();
        foreach ($this->getContent() as $key => $val) {
            $content->add($key, $val);
        }

        $this->assertFalse($content->isChanged(0, "Some words."));
        $this->assertFalse($content->isChanged(1, "Some words that are different to the ones that went before."));
        $this->assertFalse($content->isChanged(4, "And now for something completely different."));
        $this->assertTrue($content->isChanged(4, " And now for something completely different. "));
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidHashAlgorithm::class);
        $content = new ContentHash('fictionalHash123');
    }

    public function testInvalidIdentifier()
    {
        $this->expectException(InvalidHashIdentifier::class);
        $content = new ContentHash();
        $content->add(['arrayValue'], 'My string');
    }

    public function testDifferentHash()
    {
        $content = new ContentHash('md5');
        foreach ($this->getContent() as $key => $val) {
            $content->add($key, $val);
        }

        $this->assertFalse($content->isChanged(0, "Some words."));
        $this->assertFalse($content->isChanged(1, "Some words that are different to the ones that went before."));
    }

    public function testAddRemoveHash()
    {
        $content = new ContentHash();
        $content->add('one', 'my string 1');
        $content->add('two', 'my string 2');
        $content->add('three', 'my string 3');
        $content->remove(('two'));

        $this->assertFalse($content->isChanged('one', 'my string 1'));
        $this->assertTrue($content->isChanged('two', 'my string 2'));
        $this->assertFalse($content->isChanged('three', 'my string 3'));
    }

    public function testSerializedContentHash()
    {
        $content = new ContentHash();
        foreach ($this->getContent() as $key => $val) {
            $content->add($key, $val);
        }

        $serialized = serialize($content);
        unset($content);

        $content2 = unserialize($serialized);

        $this->assertFalse($content2->isChanged(0, "Some words."));
        $this->assertFalse($content2->isChanged(2, "More words that are different again."));
        $this->assertFalse($content2->isChanged(4, "And now for something completely different."));

        // isChanged replaces content so 2nd test should return false
        $this->assertTrue($content2->isChanged(1, "Some words."));
        $this->assertFalse($content2->isChanged(1, "Some words."));
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
