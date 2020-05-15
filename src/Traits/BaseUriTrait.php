<?php
declare(strict_types=1);

namespace Strata\Data\Traits;

use Strata\Data\Exception\BaseUriException;

/**
 * Base URI and other core properties for DataInterface
 *
 * @package Strata\Data\Traits
 */
trait BaseUriTrait
{
    /**
     * Base URI to use for all requests
     * @var string
     */
    protected $baseUri;

    /**
     * API endpoint to get data from
     *
     * Set this to something to default to an endpoint in your API class
     * @var string
     */
    protected $endpoint;

    /**
     * Set the base URI to use for all requests
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * Return base URI of data to use for all requests
     * @return string
     * @throws BaseUriException
     */
    public function getBaseUri(): string
    {
        if (empty($this->baseUri)) {
            throw new BaseUriException(sprintf('Base URI not set, please set via %s::setBaseUri()', get_class($this)));
        }

        return $this->baseUri;
    }

    /**
     * Set the current endpoint to use with the data request
     * @param string $endpoint
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Return endpoint URL / path
     * @return string or null if not set
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * Return URI for current data request (base URI + endpoint)
     * @return string
     * @throws BaseUriException
     */
    public function getUri(): string
    {
        return $this->getBaseUri() . '/' . $this->getEndpoint();
    }

}
