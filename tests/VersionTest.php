<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Version;

final class VersionTest extends TestCase
{

    public function testVersion()
    {
        $this->assertIsString(Version::VERSION);
        $versionBits = explode('.', Version::VERSION);
        $this->assertEquals(3, count($versionBits));
        foreach ($versionBits as $part) {
            $this->assertIsNumeric($part);
        }
    }

    public function testUserAgent()
    {
        $version = Version::VERSION;
        $this->assertEquals('Strata/' . $version . ' (https://github.com/strata/data)', Version::USER_AGENT);
    }
}
