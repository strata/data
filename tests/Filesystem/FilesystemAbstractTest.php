<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\DataNotFoundException;
use Strata\Data\Filesystem\Markdown;


final class FilesystemAbstractTest extends TestCase
{

    public function testGetOne()
    {
        $filesytem = new Markdown(__DIR__ . '/files/');

        $content = $filesytem->getOne('test-1.md');
        $this->assertStringContainsString('This is test one', $content);

        $content = $filesytem->getOne('test 4.md');
        $this->assertStringContainsString('This is test four', $content);
    }

    public function testNotFound()
    {
        $filesytem = new Markdown(__DIR__ . '/files/');

        $this->expectException(DataNotFoundException::class);
        $content = $filesytem->getOne('fake-file.md');
    }

    public function testNotFound2()
    {
        $filesytem = new Markdown(__DIR__ . '/files/');

        $this->expectException(DataNotFoundException::class);
        $content = $filesytem->from('fake-files')->getUri();
    }

    public function testList()
    {
        $filesytem = new Markdown(__DIR__ . '/files/');

        $list = $filesytem->getList();
        $this->assertEquals(4, $list->getPagination()->getTotalResults());
    }

}