<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Traits\IterableTrait;

class TestIterable implements \SeekableIterator, \Countable
{
    use IterableTrait;
}

final class IterableTraitTest extends TestCase
{
    protected $collection = ['hello', 'world', 'apple', 'pear', 'armadillo'];

    public function testInterable()
    {
        $it = new TestIterable();
        $it->setCollection($this->collection);

        $this->assertTrue(is_iterable($it));
        $this->assertEquals(5, count($it));

        foreach ($it as $key => $value) {
            switch ($key) {
                case 0:
                    $this->assertEquals('hello', $value);
                    break;
                case 1:
                    $this->assertEquals('world', $value);
                    break;
                case 2:
                    $this->assertEquals('apple', $value);
                    break;
                case 3:
                    $this->assertEquals('pear', $value);
                    break;
                case 4:
                    $this->assertEquals('armadillo', $value);
                    break;
            }
        }
    }

    public function testMethods()
    {
        $it = new TestIterable();
        $it->setCollection($this->collection);

        $this->assertEquals(5, $it->count());
        $this->assertEquals(0, $it->key());
        $it->next();
        $this->assertEquals(1, $it->key());
        $this->assertEquals('world', $it->current());
        $it->next();
        $this->assertEquals(2, $it->key());
        $it->rewind();
        $this->assertEquals(0, $it->key());
        $it->seek(4);
        $this->assertEquals('armadillo', $it->current());

        $array = $it->getCollection();
        $this->assertEquals(5, count($array));
    }

    public function testOutofBounds()
    {
        $it = new TestIterable();
        $it->setCollection($this->collection);

        $this->expectException(\OutOfBoundsException::class);
        $it->seek(10);
    }
}
