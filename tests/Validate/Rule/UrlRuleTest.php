<?php

declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Validate\Rule\UrlRule;

class UrlRuleTest extends TestCase
{
    public function testValidationRule()
    {
        $validator = new UrlRule('[url]');

        $data = ['url' => 'https://www.php.net/'];
        $this->assertTrue($validator->validate($data));

        $data = ['url' => 'hello@studio24.net'];
        $this->assertFalse($validator->validate($data));
    }
}
