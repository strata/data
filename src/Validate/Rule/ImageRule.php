<?php

declare(strict_types=1);

namespace Strata\Data\Validate\Rule;

class ImageRule extends ValidatorRuleAbstract
{
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'svg', 'webp'];

    /**
     * Is the item value valid?
     *
     * @param array|object $data
     * @return bool
     */
    public function validate($data): bool
    {
        $value = $this->getProperty($data);
        $info = pathinfo($value);
        if (!isset($info['extension'])) {
            return false;
        }
        $result = in_array(strtolower($info['extension']), self::IMAGE_EXTENSIONS);
        if (!$result) {
            $this->setErrorMessage(sprintf('%s is not a valid image filename', $this->getPropertyPath()));
        }
        return $result;
    }
}
