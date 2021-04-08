<?php

declare(strict_types=1);

namespace Cache;

use PHPUnit\Framework\TestCase;
use Strata\Data\Cache\DataHistory;
use Strata\Data\Helper\ContentHasher;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class DataHistoryTest extends TestCase
{
    const CACHE_DIR = __DIR__ . '/cache';
    const CACHE_NAMESPACE = 'strata';

    private array $data = [
        'ABC0123456789DEF0123456789',
        'ABC0123456789DEF0123456789',
        'BC0123456789DEF0123456789A',
    ];

    /**
     * This method is called after each test.
     *
     * Delete cache files
     */
    protected function tearDown(): void
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));
        $history->clear();
    }

    public function testAddGet()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));
        $history->add('123', $this->data[0]);
        $history->add('123', $this->data[1]);
        $history->add('456', $this->data[0]);
        $history->add('456', $this->data[1]);
        $history->commit();

        $entries = $history->getAll('123');
        $this->assertEquals(2, count($entries));

        $history->add('123', $this->data[0]);
        $history->add('123', $this->data[1]);
        $history->add('123', $this->data[2]);
        $history->commit();

        $entries = $history->getAll('123');
        $this->assertEquals(5, count($entries));

        $entries = $history->getAll('456');
        $this->assertEquals(2, count($entries));
    }

    public function testNew()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));
        $this->assertTrue($history->isNew(123));
        $this->assertTrue($history->isNew(456));

        $history->add('123', $this->data[0]);
        $history->add('123', $this->data[1]);
        $history->commit();

        $this->assertFalse($history->isNew(123));
        $this->assertTrue($history->isNew(456));
    }

    public function testIsChanged()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));

        // test fresh & not in cache
        $this->assertTrue($history->isChanged('123', $this->data[2]));
        $this->assertTrue($history->isChanged('456', $this->data[2]));

        $history->add('123', $this->data[0]);
        $history->commit();

        // test same + changed
        $this->assertFalse($history->isChanged('123', $this->data[1]));
        $this->assertTrue($history->isChanged('123', $this->data[2]));

        // test identical
        $this->assertTrue($history->isIdentical('123', $this->data[1]));

        $history->add('123', $this->data[1]);
        $history->add('123', $this->data[2]);
        $history->commit();

        // test same + changed
        $this->assertFalse($history->isChanged('123', $this->data[2]));
        $this->assertTrue($history->isChanged('123', $this->data[0]));

        // test identical
        $this->assertTrue($history->isIdentical('123', $this->data[2]));
    }

    public function testMetadata()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));
        $history->add('123', $this->data[1], ['message' => 'Hello']);
        $history->add('123', $this->data[2], ['message' => 'World']);
        $history->commit();

        $this->assertEquals(['message' => 'World'], $history->getLastItem(123, 'metadata'));
    }

    public function testGetLastItem()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));

        // test empty
        $this->assertNull($history->getLastItem(123));

        $now = new \DateTimeImmutable();
        $history->add('123', $this->data[1], ['message' => 'Hello']);
        $history->commit();

        $item = $history->getLastItem(123);
        $this->assertEquals('Hello', $item['metadata']['message']);
        $this->assertEquals(['message' => 'Hello'], $history->getLastItem(123, 'metadata'));
        $this->assertEquals(ContentHasher::hash($this->data[1]), $history->getLastItem(123, 'content_hash'));

        // Check datetime of last item is within 30 seconds of $now (should be close to identical)
        $interval = $now->diff(new \DateTime($history->getLastItem(123, 'updated')));
        $this->assertTrue($interval->format('i') < 30);

        // Test invalid item field
        $this->expectException('Strata\Data\Exception\CacheException');
        $history->getLastItem(123, 'invalid');
    }

    public function testPurge()
    {
        $history = new DataHistory(new FilesystemAdapter(self::CACHE_NAMESPACE, 0, self::CACHE_DIR));
        $history->add('123', $this->data[0]);
        $history->add('123', $this->data[1]);
        $history->add('123', $this->data[2]);
        $history->add('456', $this->data[0]);
        $history->add('456', $this->data[1]);
        $history->commit();

        $entries = $history->getAll('123');
        $this->assertEquals(3, count($entries));

        // Set probability to 1.0 to guarantee purge runs
        // Should not remove anything
        $entries = $history->purge($entries, 1.0);
        $this->assertEquals(3, count($entries));

        // Should delete everything
        $now = new \DateTime();
        $now->modify('+2 day');
        $history->setMaxHistoryDays(1);
        $entries = $history->purge($entries, 1.0, $now);
        $this->assertEquals(0, count($entries));
    }
}
