<?php

declare(strict_types=1);

namespace Strata\Data\Transform\Data;

use Strata\Data\Helper\UnionTypes;
use Strata\Data\Transform\NotTransformedInterface;
use Strata\Data\Transform\NotTransformedTrait;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Rename keys in a data array
 *
 * Can rename hierarchical arrays
 */
class RenameFields extends DataAbstract implements NotTransformedInterface
{
    use NotTransformedTrait;

    private array $propertyPaths;

    /**
     * RenameFields constructor.
     * @param array $propertyPaths Array of property paths to rename ['new_name' => 'old_name']
     */
    public function __construct(array $propertyPaths)
    {
        $this->setPropertyPaths($propertyPaths);
    }

    public function setPropertyPaths(array $propertyPaths)
    {
        $this->propertyPaths = $propertyPaths;
    }

    /**
     * Whether this transformer can transform data
     *
     * @param $data
     * @return bool
     */
    public function canTransform($data): bool
    {
        return UnionTypes::is($data, 'array', 'object');
    }

    /**
     * Transform array of data into something else
     *
     * @param $data
     * @return mixed
     */
    public function transform($data)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($this->propertyPaths as $new => $old) {
            if (!$propertyAccessor->isReadable($data, $old)) {
                $this->addNotTransformed($new);
                continue;
            }
            if (!$propertyAccessor->isWritable($data, $new)) {
                $this->addNotTransformed($new);
                continue;
            }

            // Set value to new array key
            $value = $propertyAccessor->getValue($data, $old);
            $propertyAccessor->setValue($data, $new, $value);

            // Seek old array key and unset it
            $propertyPath = new PropertyPath($old);
            $parent =& $data;
            $elements = $propertyPath->getElements();
            if ($elements > 1) {
                for ($x = 0; $x < count($elements) - 1; $x++) {
                    $key = $elements[$x];
                    $parent =& $parent[$key];
                }
            }
            end($elements);
            unset($parent[current($elements)]);
        }

        return $data;
    }
}
