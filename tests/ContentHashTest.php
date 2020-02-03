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
        $content1 = <<<EOD
May your coming year be filled with magic and dreams and good madness. I hope you read some fine books and kiss someone 
who thinks you're wonderful, and don't forget to make some art - write or draw or build or sing or live as only you can. 
And I hope, somewhere in the next year, you surprise yourself.

EOD;

        $content2 = <<<EOD
Some words are here that are not the same as the words above by the fine Neil Gaiman. These words are different. 

EOD;

        $contentHash = new ContentHash();
        $a = $contentHash->hash($content1);
        $b = $contentHash->hash($content2);
        $c = $contentHash->hash($content1);
        $d = $contentHash->hash(trim($content1));

        $this->assertFalse($contentHash->compare($a, $b));
        $this->assertTrue($contentHash->compare($a, $c));
        $this->assertFalse($contentHash->compare($a, $d));
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidHashAlgorithm::class);
        $contentHash = new ContentHash('fictionalHash123');
    }

    public function testDifferentHash()
    {
        $content1 = <<<EOD
Some words that are different to the ones that went before.

EOD;

        $content2 = <<<EOD
More words that are different again. 

EOD;

        $contentHash = new ContentHash('md5');
        $a = $contentHash->hash($content1);
        $b = $contentHash->hash($content2);
        $c = $contentHash->hash($content1);

        $this->assertFalse($contentHash->compare($a, $b));
        $this->assertTrue($contentHash->compare($a, $c));
    }
}
