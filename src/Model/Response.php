<?php
declare(strict_types=1);

namespace Strata\Data\Model;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Populate\PopulateInterface;
use Strata\Data\Traits\IterableTrait;
use Strata\Data\Pagination\Pagination;
use Strata\Data\Transform\ArrayStrategy;
use Strata\Data\Transform\TransformStrategyInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Object to represent a response to a data endpoint
 *
 * Usage:
 * $response = new Response();
 * $response->setRawData($data);
 * Populate strategy...
 * $response->add(new Item());
 *
 * // Get one (or first) item
 * $item = $response->get();
 *
 * // Get headers from an HTTP response
 * $contentType = $response->getMeta('Content-Type');
 *
 * // Iterate over many items
 * foreach ($response as $item) {
 *     // ...
 * }
 *
 * @package Strata\Data\Model
 */
class Response
{
    use IterableTrait;

    const MASTER_RESPONSE = 1;
    const SUB_RESPONSE = 2;
    const NESTED_PROPERTY_SEPARATOR = '.';

    private string $requestId;
    private int $responseType = self::MASTER_RESPONSE;
    private string $uri;
    private array $meta = [];
    private array $rawContent = [];
    private Pagination $pagination;
    private array $transformStrategy = [];
    private array $populateStrategy = [];

    /**
     * Constructor
     *
     * @param string $requestId Unique identifier, must be a valid cache key
     * @oaram ?string $uri URI that represents this request, for information only
     * @param int $requestType Response::MASTER_RESPONSE or Response::SUB_RESPONSE
     * @throws InvalidArgumentException When $id is not valid
     */
    public function __construct(string $requestId, ?string $uri = null, int $requestType = self::MASTER_RESPONSE)
    {
        $this->setRequestId($requestId);
        if ($uri !== null) {
            $this->setUri($uri);
        }
        $this->responseType = $requestType;
    }

    /**
     * Set unique identifier for this request
     *
     * Must be valid for use as a cache key
     *
     * @param string $id
     * @throws InvalidArgumentException When $id is not valid
     */
    public function setRequestId(string $id)
    {
        $id = CacheItem::validateKey($id);
        $this->requestId = $id;
    }

    /**
     * Return unique identifier for this request
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Returns the request type this response represents
     *
     * @return int One of Response::MASTER_RESPONSE or Response::SUB_RESPONSE
     */
    public function getRequestType(): int
    {
        return $this->requestType;
    }

    /**
     * Checks if this is a master response
     *
     * @return bool True if the request is a master request
     */
    public function isMasterResponse(): bool
    {
        return Response::MASTER_RESPONSE === $this->requestType;
    }

    /**
     * Set URI, which indicates where data is loaded from
     *
     * @param string $uri
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * Return URI, which indicates where data is loaded from
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set raw response content so we can lazy load data when accessed
     *
     * @param array $content
     */
    public function setRawContent(array $content)
    {
        $this->rawContent = $content;
    }

    public function getRawData(): array
    {
        return $this->rawContent;
    }

    /**
     * Explode property reference into an array of nested property references
     *
     * E.g. 'data.title' returns ['data', 'title']
     *
     * @param string $propertyReference
     * @return array
     */
    protected function explodeNestedProperty(string $propertyReference): array
    {
        return explode(self::NESTED_PROPERTY_SEPARATOR, $propertyReference);
    }

    /**
     * Return nested property from an array, or null if property does not exist
     *
     * @param Item $item
     * @param string $propertyReference Property reference, with nested properties separated by a dot
     * @return mixed|null
     */
    protected function getNestedProperty(array $data, string $propertyReference)
    {
        foreach ($this->explodeNestedProperty($propertyReference) as $propertyName) {
            if (isset($data[$propertyName])) {
                $data = $data[$propertyName];
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Return nested property from current item, or null if does not exist
     *
     * @param string $propertyReference
     * @return mixed|null
     */
    public function getProperty(string $propertyReference)
    {
        $item = $this->current();
        if (!is_array($item)) {
            return null;
        }
        return $this->getNestedProperty($this->getItem(), $propertyReference);
    }

    /**
     * Return nested property from raw data, or null if does not exist
     *
     * @param string $propertyReference
     * @return mixed|null
     */
    public function getRawProperty(string $propertyReference)
    {
        if (!is_array($this->rawContent)) {
            return null;
        }
        return $this->getNestedProperty($this->rawContent, $propertyReference);
    }


    /* @todo delete
    public function setErrorMessage(string $message): void
    {
        $this->errorMessage = $message;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorData(array $data): void
    {
        $this->errorData = $data;
    }

    public function addErrorDataFromString(string $data): void
    {
        $this->errorData[] = $data;
    }

    public function getErrorData(): array
    {
        return $this->errorData;
    }
    */

    public function setMeta(string $key, $value)
    {
        $this->meta[$key] = $value;
    }

    public function setMetaFromArray(array $metadata)
    {
        foreach ($metadata as $key => $value) {
            $this->setMeta($key, $value);
        }
    }

    /**
     * Whether meta data exists
     *
     * @param string $key
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    /**
     * Return meta data, or null if not set
     *
     * @param string $key
     * @return mixed|null
     */
    public function getMeta(string $key)
    {
        if (isset($this->meta[$key])) {
            return $this->meta[$key];
        }
        return null;
    }

    /**
     * Return all meta data
     *
     * @return array
     */
    public function getAllMeta(): array
    {
        return $this->meta;
    }

    /**
     * Create a new item, add it to the collection and return it
     *
     * @param string $identifier
     * @param $content
     * @return Item
     * @throws \Strata\Data\Exception\DecoderException
     * @throws \Strata\Data\Exception\ItemContentException
     */
    public function add(string $identifier, $content): Item
    {
        $item = new Item($identifier);
        $item->setContent($content);
        $this->collection[] = $item;
        return $item;
    }

    /**
     * Return current item
     * @return mixed
     */
    public function current(): Item
    {
        return $this->collection[$this->position];
    }

    /**
     * Return first, or current, item if multiple items
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->current();
    }

    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Add a populate strategy to populate data for this response
     *
     * @param PopulateInterface $populate
     */
    public function populateStrategy(PopulateInterface $populate)
    {
        $this->populateStrategy[] = $populate;
    }

    /**
     * Populate the response object from raw data
     */
    public function populate()
    {
        /** @var PopulateInterface $strategy */
        foreach ($this->populateStrategy as $strategy) {
            $strategy->populate($this);
        }
    }

    /**
     * Add data transformation strategy
     *
     * @param TransformStrategyInterface $transformStrategy
     */
    public function transformStrategy(TransformStrategyInterface $transformStrategy)
    {
        $this->transformStrategy[] = $transformStrategy;
    }

    /**
     * Run data transformers
     */
    public function transform()
    {
        /** @var TransformStrategyInterface $transformer */
        foreach ($this->transformStrategy as $strategy) {
            $strategy->transform($this);
        }
    }


}
