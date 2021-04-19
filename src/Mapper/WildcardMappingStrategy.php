<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

use Strata\Data\Helper\UnionTypes;
use Strata\Data\Transform\PropertyAccessorTrait;
use Strata\Data\Transform\TransformerChain;

class WildcardMappingStrategy implements MappingStrategyInterface
{
    use PropertyAccessorTrait;
    use TransformerTrait;

    private array $ignore = [];

    /**
     * Set fields to map from data to your new array/object
     *
     * @param array $transformers Array of transformers to apply to mapped data
     */
    public function __construct(array $ignore = [], array $transformers = [])
    {
        if (!empty($ignore)) {
            $this->setIgnore($ignore);
        }
        $this->setTransformers($transformers);
    }

    /**
     * Set fields to ignore when mapping data
     *
     * @param array $ignore array of fieldnames to ignore when mapping data
     */
    public function setIgnore(array $ignore)
    {
        foreach ($ignore as $field) {
            $this->ignore[] = strtolower($field);
        }
    }

    /**
     * Map array of data to an item (array or object)
     *
     * @param array $data
     * @param array|object $item
     * @return mixed
     */
    public function mapItem(array $data, $item)
    {
        if (!UnionTypes::arrayOrObject($item)) {
            throw new \TypeError(sprintf('$item argument must be an array or object, %s passed', gettype($item)));
        }
        $propertyAccessor = $this->getPropertyAccessor();

        // Loop through data to map to new item (destination => source)
        foreach ($data as $field => $value) {
            if (in_array(strtolower($field), $this->ignore)) {
                continue;
            }
            switch (gettype($item)) {
                case 'array':
                    $item[$field] = $value;
                    break;
                case 'object':
                    $propertyAccessor->setValue($item, $field, $value);
                    break;
            }
        }

        // Transform data
        if ($this->getTransformerChain() instanceof TransformerChain) {
            $item = $this->getTransformerChain()->transform($item);
        }

        return $item;
    }
}
