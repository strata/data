<?php

declare(strict_types=1);

namespace Strata\Data;

use Composer\InstalledVersions;

class Version
{
    const PACKAGE = 'strata\data';

    /**
     * Return current version of Strata Data
     *
     * Requires Composer 2, or returns null if not found
     *
     * @return string|null
     */
    public static function getVersion(): ?string
    {
        if (class_exists('\Composer\InstalledVersions')) {
            if (InstalledVersions::isInstalled(self::PACKAGE)) {
                return InstalledVersions::getVersion(self::PACKAGE);
            }
        }
        return null;
    }

}
