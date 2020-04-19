<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Permissions;

final class PermissionTest extends TestCase
{

    public function testDefaultValues()
    {
        $permission = new Permissions();

        $this->assertTrue($permission->read());
        $this->assertFalse($permission->write());
        $this->assertFalse($permission->delete());
        $this->assertEquals('READ', $permission->__toString());

        $this->assertEquals('READ', $permission->getName(Permissions::READ));
        $this->assertEquals('WRITE', $permission->getName(Permissions::WRITE));
        $this->assertEquals('DELETE', $permission->getName(Permissions::DELETE));
    }

    public function testCustomValues()
    {
        $permission = new Permissions(Permissions::READ | Permissions::WRITE);

        $this->assertTrue($permission->read());
        $this->assertTrue($permission->write());
        $this->assertFalse($permission->delete());
        $this->assertEquals('READ, WRITE', $permission->__toString());

        $permission = new Permissions(Permissions::READ | Permissions::WRITE | Permissions::DELETE);

        $this->assertTrue($permission->read());
        $this->assertTrue($permission->write());
        $this->assertTrue($permission->delete());
        $this->assertEquals('READ, WRITE, DELETE', $permission->__toString());

        $permission = new Permissions(Permissions::DELETE | Permissions::WRITE);

        $this->assertFalse($permission->read());
        $this->assertTrue($permission->write());
        $this->assertTrue($permission->delete());
        $this->assertEquals('WRITE, DELETE', $permission->__toString());
    }

}
