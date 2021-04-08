<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Transform\Data\RenameFields;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class RenameFieldsTest extends TestCase
{
    public function testRename()
    {
        $data = [
            'full_name' => 'Fred Jones',
            'age' => 42,
        ];
        $rename = new RenameFields(['[name]' => '[full_name]']);
        $data = $rename->transform($data);

        $this->assertSame('Fred Jones', $data['name']);
        $this->assertArrayNotHasKey('full_name', $data);
        $this->assertFalse($rename->hasNotTransformed());

        $data = [
            'people' => [
                'name' => 'Fred Jones',
                'years' => 42,
            ]
        ];

        $rename->setPropertyPaths(['[people][age]' => '[people][years]']);
        $data = $rename->transform($data);
        $this->assertSame(42, $data['people']['age']);
        $this->assertArrayNotHasKey('years', $data['people']);
    }

    public function testNotTransformed()
    {
        $rename = [
            '[name]' => '[full_name]',
            '[category]' => '[category_name]',
        ];
        $data = [
            'full_name' => 'Fred Jones',
            'age' => 42,
        ];
        $rename = new RenameFields($rename);
        $data = $rename->transform($data);

        $this->assertTrue($rename->hasNotTransformed());
        $this->assertSame(['[category]'], $rename->getNotTransformed());
    }

}
