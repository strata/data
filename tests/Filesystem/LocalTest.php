<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\DataNotFoundException;
use Strata\Data\Filesystem\Local;

final class LocalTest extends TestCase
{

    /** @var Local */
    protected $filesystem;

    public function setUp(): void
    {
        $this->filesystem = new Local(__DIR__ . '/files/');
    }

    public function testGetOne()
    {
        $content = $this->filesystem->getOne('test-1.md');
        $this->assertStringContainsString('This is test one', $content);

        $content = $this->filesystem->getOne('test 4.md');
        $this->assertStringContainsString('This is test four', $content);
    }

    public function _testNotFound()
    {
        $this->expectException(DataNotFoundException::class);
        $content = $this->filesystem->getOne('fake-file.md');
    }

    public function _testNotFound2()
    {
        $this->expectException(DataNotFoundException::class);
        $content = $this->filesystem->from('fake-files')->getUri();
    }

    public function testList()
    {
        $list = $this->filesystem->getList();
        $this->assertEquals(4, $list->getPagination()->getTotalResults());

        foreach ($list as $key => $item) {
            switch ($key) {
                case 0:
                    $this->assertStringContainsString('one', $item);
                    break;
            }
        }
    }
}
