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
        $this->assertFalse($permission->create());
        $this->assertFalse($permission->update());
        $this->assertFalse($permission->delete());
        $this->assertEquals('READ', $permission->__toString());

        $this->assertEquals('READ', $permission->getName(Permissions::READ));
        $this->assertEquals('CREATE', $permission->getName(Permissions::CREATE));
        $this->assertEquals('UPDATE', $permission->getName(Permissions::UPDATE));
        $this->assertEquals('DELETE', $permission->getName(Permissions::DELETE));
    }

    public function testCustomValues()
    {
        $permission = new Permissions(Permissions::READ | Permissions::UPDATE);

        $this->assertTrue($permission->read());
        $this->assertTrue($permission->update());
        $this->assertFalse($permission->create());
        $this->assertFalse($permission->delete());
        $this->assertEquals('READ, UPDATE', $permission->__toString());

        $permission = new Permissions(Permissions::READ | Permissions::UPDATE | Permissions::CREATE | Permissions::DELETE);

        $this->assertTrue($permission->read());
        $this->assertTrue($permission->update());
        $this->assertTrue($permission->create());
        $this->assertTrue($permission->delete());
        $this->assertEquals('READ, CREATE, UPDATE, DELETE', $permission->__toString());

        $permission = new Permissions(Permissions::DELETE | Permissions::READ);

        $this->assertTrue($permission->read());
        $this->assertFalse($permission->update());
        $this->assertFalse($permission->create());
        $this->assertTrue($permission->delete());
        $this->assertEquals('READ, DELETE', $permission->__toString());

        $permission = new Permissions(Permissions::DELETE);

        $this->assertFalse($permission->read());
        $this->assertFalse($permission->update());
        $this->assertFalse($permission->create());
        $this->assertTrue($permission->delete());
        $this->assertEquals('DELETE', $permission->__toString());
    }

}
