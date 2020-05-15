<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Collection\ListAbstract;

class TestList extends ListAbstract {
}

final class ListAbstractTest extends TestCase
{
    protected $collection = ['one','two','three'];

    public function testPagination()
    {
        $list = new TestList($this->collection);
        $list->getPagination();
        $this->assertEquals(3, $list->getPagination()->getTotalResults());
        $this->assertEquals(3, $list->getPagination()->getResultsPerPage());
        $this->assertEquals(1, $list->getPagination()->getPage());
        $this->assertEquals(1, $list->getPagination()->getTotalPages());
        $this->assertEquals(1, $list->getPagination()->getFrom());
        $this->assertEquals(3, $list->getPagination()->getTo());
        $this->assertEquals('one', $list->current());
    }

    public function testMetadata()
    {
        $list = new TestList($this->collection);
        $list->addMetadata('name', 'test1');
        $this->assertTrue($list->hasMetadata('name'));
        $this->assertEquals('test1', $list->getMetadata('name'));
        $this->assertFalse($list->hasMetadata('testing'));
        $this->assertNull($list->getMetadata('testing'));
        $this->assertEquals(1, count($list->getAllMetadata()));

        $list->setMetadata(['testing' => true, 'foo' => 'bar']);
        $this->assertFalse($list->hasMetadata('name'));
        $this->assertNull($list->getMetadata('name'));
        $this->assertEquals('bar', $list->getMetadata('foo'));
        $this->assertEquals(2, count($list->getAllMetadata()));
    }

}