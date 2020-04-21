<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

use Strata\Data\Permissions;
use Strata\Data\Exception\PermissionException;

/**
 * Permissions for accessing the API
 *
 * Used to protect against accidental misuse
 * @package Strata\Data\Traits
 */
trait CheckPermissions
{

    /** @var Permissions */
    protected $permissions;

    /**
     * Set permissions for accessing the API
     * @param Permissions $permissions
     */
    public function setPermissions(Permissions $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Get permissions for accessing the API
     * @return Permissions
     */
    public function getPermissions(): Permissions
    {
        return $this->permissions;
    }

    /**
     * Check whether you are allowed to perform the following action on the API
     *
     * @param int $action
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function checkPermission(int $action, $throw = true): bool
    {
        if ($this->permissions->isAllowed($action)) {
            return true;
        }

        if (!$throw) {
            return false;
        }

        $message = sprintf(
            'Permission not allowed error. Requested permission: %s, Allowed permissions: %s',
            $this->permissions->getName($action),
            $this->permissions->__toString()
        );
        throw new PermissionException($message);
    }

    /**
     * Check we have permission to read data
     *
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionRead($throw = true): bool
    {
        return $this->checkPermission(Permissions::READ, $throw);
    }

    /**
     * Check we have permission to create data
     *
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionCreate($throw = true): bool
    {
        return $this->checkPermission(Permissions::CREATE, $throw);
    }

    /**
     * Check we have permission to update data
     *
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionUpdate($throw = true): bool
    {
        return $this->checkPermission(Permissions::UPDATE, $throw);
    }

    /**
     * Check we have permission to delete data
     *
     * @param bool $throw Throw an exception by default, or return bool if $throw is set to false
     * @return bool
     * @throws PermissionException
     */
    public function permissionDelete($throw = true): bool
    {
        return $this->checkPermission(Permissions::DELETE, $throw);
    }

}
