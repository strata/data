<?php
declare(strict_types=1);

namespace Strata\Data\Populate;

use Strata\Data\Exception\PopulateException;
use Strata\Data\Model\Response;

trait PropertyTrait
{
    const TYPE_INT = 1;

    protected function filterType(string $field, $value, int $type)
    {
        switch ($type) {
            case self::TYPE_INT:
                if (!is_numeric($value)) {
                    throw new PopulateException(sprintf('Error retrieving property, "%s" is not a number', $field));
                }
                break;
        }
        return $value;
    }

    /**
     * Return property field
     *
     * @param string $propertyReference Property reference, use dots for nested properties
     * @param Response $response
     * @param ?int $type If passed, checks for the type
     * @return array|mixed
     * @throws PopulateException
     */
    public function getRawProperty(string $propertyReference, Response $response, ?int $type = null)
    {
        $value = $response->getRawProperty($propertyReference);
        if ($value === null) {
            throw new PopulateException(sprintf('Cannot retrieve property, "%s" does not exist or is null', $propertyReference));
        }

        if ($type !== null) {
            $value = $this->filterType($propertyReference, $value, $type);
        }

        return $value;
    }

    /**
     * Return metadata field
     *
     * @param string $field
     * @param Response $response
     * @param ?int $type If passed, checks for the type
     * @return mixed|null
     * @throws PopulateException
     */
    public function getMetadata(string $field, Response $response, ?int $type = null)
    {
        if (!$response->hasMeta($field)) {
            throw new PopulateException(sprintf('Cannot retrieve metadata, "%s" does not exist', $field));
        }

        $value = $response->getMeta($field);

        if ($type !== null) {
            $value = $this->filterType($field, $value, $type);
        }

        return $value;
    }

}