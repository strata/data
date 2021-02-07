<?php
declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

use Strata\Data\Model\Item;

class ImageRule extends RuleAbstract
{
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'svg', 'webp'];

    /**
     * Is the item value valid?
     *
     * @param string $propertyReference
     * @param Item $item
     * @return bool
     */
    public function validate(string $propertyReference, Item $item): bool
    {
        $info = pathinfo($this->getProperty($item, $propertyReference));
        if (!isset($info['extension'])) {
            return false;
        }
        $result = in_array(strtolower($info['extension']), self::IMAGE_EXTENSIONS);
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not a valid image filename', $propertyReference));
        }
        return $result;
    }
}