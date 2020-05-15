<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Traits\BaseUriTrait;

class TestBaseUri {
    use BaseUriTrait;
}

final class BaseUriTest extends TestCase
{

    public function testBaseUri()
    {
        $class = new TestBaseUri();
        $class->setBaseUri('test-uri');

        $this->assertEquals('test-uri', $class->getBaseUri());
    }

    public function testNoBaseUri()
    {
        $class = new TestBaseUri();

        $this->expectException(BaseUriException::class);
        $class->getBaseUri();
    }

    public function testEndpoint()
    {
        $class = new TestBaseUri();
        $class->setBaseUri('test-uri');
        $class->setEndpoint('my-location');

        $this->assertEquals('my-location', $class->getEndpoint());
        $this->assertEquals('test-uri/my-location', $class->getUri());
    }

}