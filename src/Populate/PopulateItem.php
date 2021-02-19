<?php
declare(strict_types=1);

namespace Strata\Data\Populate;

use Strata\Data\Exception\PopulateException;
use Strata\Data\Model\Response;

class PopulateItem implements PopulateInterface
{
    use PropertyTrait;

    private bool $fromRoot = false;
    private ?string $property;

    /**
     * Set the property name to populate data from
     *
     * @param $property
     * @return PopulateItem Fluent interface
     */
    public function fromProperty($property): PopulateItem
    {
        $this->property = $property;
        return $this;
    }


    /**
     * Popluate data from root of raw data
     *
     * @return PopulateItem Fluent interface
     */
    public function fromRoot(): PopulateItem
    {
        $this->fromRoot = true;
        return $this;
    }

    /**
     * Returns a populated response object
     *
     * Data is populated from raw data according to rules of the Populate object
     *
     * @param Response $response
     * @return Response
     * @throws PopulateException If populate via property and that property does not exist
     */
    public function populate(Response $response): Response
    {
        if ($this->fromRoot) {
            $response->add($response->getRequestId(), $response->getRawData());
            return $response;
        }

        if (empty($this->property)) {
            throw new PopulateException('Cannot populate data. Please choose populate method via PopulateItem::fromRoot() or PopulateItem::fromProperty()');
        }

        $item = $this->getRawProperty($this->property, $response);
        $response->add($response->getRequestId(), $item);

        return $response;
    }


}