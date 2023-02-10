<?php

declare(strict_types=1);

namespace Strata\Data;

/**
 * Class to manage allowed actions on an API and to protect against misuse
 *
 */
class Permissions
{
    const READ      = 1;
    const CREATE    = 2;
    const UPDATE    = 4;
    const DELETE    = 8;

    protected $allowed;

    /**
     * Set which actions you are allowed to access via this API
     *
     * @param int $actions (Permissions::READ, Permissions::CREATE, Permissions::UPDATE, Permissions::DELETE)
     */
    public function __construct(int $actions = self::READ)
    {
        $this->allowed = $actions;
    }

    public function isAllowed(int $action): bool
    {
        return (($this->allowed & $action) !== 0);
    }

    /**
     * Do you have permission for READ access?
     *
     * @return bool
     */
    public function read(): bool
    {
        return $this->isAllowed(self::READ);
    }

    /**
     * Do you have permission for WRITE access?
     *
     * @return bool
     */
    public function create(): bool
    {
        return $this->isAllowed(self::CREATE);
    }

    /**
     * Do you have permission for DELETE access?
     *
     * @return bool
     */
    public function update(): bool
    {
        return $this->isAllowed(self::UPDATE);
    }

    /**
     * Do you have permission for DELETE access?
     *
     * @return bool
     */
    public function delete(): bool
    {
        return $this->isAllowed(self::DELETE);
    }

    /**
     * Return string name of permission
     *
     * @param int $action
     * @return ?string
     */
    public function getName(int $action): ?string
    {
        switch ($action) {
            case self::READ:
                return 'READ';
                break;

            case self::CREATE:
                return 'CREATE';
                break;

            case self::UPDATE:
                return 'UPDATE';
                break;

            case self::DELETE:
                return 'DELETE';
                break;
        }
        return null;
    }

    /**
     * String representation of current permissions
     *
     * @return string
     */
    public function __toString()
    {
        $results = [];
        if ($this->read()) {
            $results[] = $this->getName(self::READ);
        }
        if ($this->create()) {
            $results[] = $this->getName(self::CREATE);
        }
        if ($this->update()) {
            $results[] = $this->getName(self::UPDATE);
        }
        if ($this->delete()) {
            $results[] = $this->getName(self::DELETE);
        }

        if (!empty($results)) {
            return implode(', ', $results);
        }
        return 'No permissions set!';
    }
}
