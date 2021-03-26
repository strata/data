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
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $data = [
            'name' => 'Fred Jones',
            'age' => 42,
        ];
        $rename = new RenameFields(['[name]' => '[full_name]']);
        $data = $rename->transform($data);
        $this->assertEquals('Fred Jones', $propertyAccessor->getValue($data, '[full_name]'));
        $this->assertArrayNotHasKey('name', $data);

        $data = [
            'people' => [
                'name' => 'Fred Jones',
                'age' => 42,
            ]
        ];

        $rename->setPropertyPaths(['[people][age]' => '[people][number]']);
        $data = $rename->transform($data);
        $this->assertEquals(42, $propertyAccessor->getValue($data, '[people][number]'));
        $this->assertArrayNotHasKey('age', $data['people']);
    }
}
