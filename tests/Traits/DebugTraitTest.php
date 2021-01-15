<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Traits\DebugTrait;

class TestDebug
{
    use DebugTrait;
}

final class DebugTraitTest extends TestCase
{

    public function testBaseUri()
    {
        $class = new TestDebug();
        $class->logger('do something');

        $class->logger()->log('message');
    }

}
