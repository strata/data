<?php
declare(strict_types=1);

namespace Strata\Data\Populate;

use Strata\Data\Exception\PopulateException;
use Strata\Data\Model\Response;

class PopulateMetadata implements PopulateInterface
{
    use PropertyTrait;

    private ?string $property;
    private array $metadata = [];

    /**
     * Set the property name to populate data from
     *
     * @param $property
     * @return PopulateMetadata Fluent interface
     */
    public function fromProperty($property): PopulateMetadata
    {
        $this->property = $property;
        return $this;
    }

    /**
     * Populate metadata from an array
     *
     * @param array $array
     * @return PopulateMetadata
     */
    public function fromArray(array $array): PopulateMetadata
    {
        $this->metadata = $array;
    }

    /**
     * Returns a populated response object
     *
     * @param Response $response
     * @return Response
     * @throws PopulateException If populate via property and that property does not exist
     */
    public function populate(Response $response): Response
    {
        if (!empty($this->metadata)) {
            $response->setMetaFromArray($this->metadata);
        }

        if (empty($this->metadata) && empty($this->property)) {
            throw new PopulateException('Cannot populate data. Please choose populate method via PopulateMetadata::fromArray() or PopulateMetadata::fromProperty()');
        }

        $metadata = $this->getRawProperty($this->property, $response);
        foreach ($metadata as $key => $value) {
            $response->setMeta($key, $value);
        }

        return $response;
    }


}