<?php
declare(strict_types=1);

namespace Strata\Data\Model;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Traits\IterableTrait;
use Strata\Data\Pagination\Pagination;
use Strata\Data\Transform\ArrayCollectionStrategy;
use Strata\Data\Transform\TransformStrategyInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Object to represent a response to a data endpoint
 *
 * Usage:
 * $response = new Response();
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

    private string $requestId;
    private int $responseType = self::MASTER_RESPONSE;
    private string $uri;
    private array $meta = [];
    private Pagination $pagination;
    private array $transformers = [];
    private TransformStrategyInterface $defaultCollectionStrategy;

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

    public function getMeta(string $key)
    {
        if (isset($this->meta[$key])) {
            return $this->meta[$key];
        }
        return null;
    }

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

    public function addTransformer(TransformStrategyInterface $transformStrategy)
    {
        $this->transformers[] = $transformStrategy;
    }

    /**
     * @return TransformStrategyInterface[]
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }

    public function getCollectionStrategy(): TransformStrategyInterface
    {
        if (!($this->defaultCollectionStrategy instanceof TransformStrategyInterface)) {
            $this->defaultCollectionStrategy = new ArrayCollectionStrategy();
        }
        return $this->defaultCollectionStrategy;
    }

    /**
     * Load a collection of items
     *
     * @param string $property
     */
    public function loadCollection(string $property): void
    {
        $strategy = $this->getCollectionStrategy();
        $strategy->transform($this, ['property' => $property]);
    }

}
