<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Exception\PermissionException;
use Strata\Data\Permissions;
use Strata\Data\Traits\CheckPermissionsTrait;

class TestPermissions
{
    use CheckPermissionsTrait;
}

final class CheckPermissionsTest extends TestCase
{
    public function testPermissionMethods()
    {
        $class = new TestPermissions();
        $class->setPermissions(new Permissions());

        $this->assertTrue($class->getPermissions() instanceof Permissions);
        $this->assertTrue($class->permissionRead(false));
        $this->assertFalse($class->permissionCreate(false));
        $this->assertFalse($class->permissionUpdate(false));
        $this->assertFalse($class->permissionDelete(false));
    }

    public function testPermissionException()
    {
        $class = new TestPermissions();
        $class->setPermissions(new Permissions());

        $this->expectException(PermissionException::class);
        $class->permissionDelete();
    }
}
