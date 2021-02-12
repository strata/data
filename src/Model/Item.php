<?php
declare(strict_types=1);

namespace Strata\Data\Model;

use Strata\Data\Decode\DecoderInterface;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\ItemContentException;
use Strata\Data\Validate\ValidationRules;

/**
 * Item returned from an API request, either an array of data properties or a string
 *
 * Usage:
 * use Strata\Data\Decode\Json;
 *
 * $item = new Item($uri, $data, new Json());
 *
 * // Access content as an array
 * echo $item['title'];
 *
 * // Or if content is a string, access as a string
 * echo $item;
 *
 * @package Strata\Data\Data
 */
class Item implements \ArrayAccess
{
    const NESTED_PROPERTY_SEPARATOR = '.';
    private string $identifier;
    private $content;
    private array $meta = [];

    /**
     * Constructor
     *
     * @param string $identifier Unique identifier for this item
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Return item identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set content from data response
     *
     * If a decoder is passed, then content is transformed to the required format
     *
     * @param array|string $content
     * @param ?DecoderStrategy $decoder Decoder to transform content
     * @throws ItemContentException
     * @throws DecoderException
     */
    public function setContent($content, ?DecoderInterface $decoder = null)
    {
        if ($decoder !== null) {
            $this->content = $decoder->decode($content);
        } else {
            $this->content = $content;
        }
    }

    /**
     * Return item content
     *
     * @return array|string $content
     */
    public function getContent()
    {
        return $this->content;
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

    /**
     * Return nested property from an item, or null if property does not exist
     *
     * @param Item $item
     * @param string $propertyReference Property reference, with nested properties separated by a dot
     * @return mixed|null
     */
    public function getProperty(string $propertyReference)
    {
        if (!$this->containsArray()) {
            return null;
        }

        $nestedItem = $this->getContent();
        foreach ($this->explodeNestedProperty($propertyReference) as $propertyName) {
            if (isset($nestedItem[$propertyName])) {
                $nestedItem = $nestedItem[$propertyName];
            } else {
                return null;
            }
        }
        return $nestedItem;
    }

    /**
     * Set item metadata
     *
     * @param string $key
     * @param $value
     */
    public function setMeta(string $key, $value)
    {
        $this->meta[$key] = $value;
    }

    /**
     * Set item metadata from an array
     *
     * @param array $metadata
     */
    public function setMetaFromArray(array $metadata)
    {
        foreach ($metadata as $key => $value) {
            $this->setMeta($key, $value);
        }
    }

    /**
     * Return item metadata
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
     * Return all metadata
     *
     * @return array
     */
    public function getAllMeta(): array
    {
        return $this->meta;
    }

    /**
     * Whether the item contains string content
     *
     * @return bool
     */
    public function containsString(): bool
    {
        return is_string($this->content);
    }

    /**
     * Whether the item contains array content
     *
     * @return bool
     */
    public function containsArray(): bool
    {
        return is_array($this->content);
    }

    private function throwExceptionIfNotString()
    {
        if (!$this->containsString()) {
            throw new ItemContentException('Item content is an array so cannot be accessed as a string');
        }
    }

    private function throwExceptionIfNotArray()
    {
        if (!$this->containsArray()) {
            throw new ItemContentException('Item content is a string so cannot be accessed as an array');
        }
    }

    /**
     * Return item content if a string
     *
     * @return string
     * @throws ItemContentException If item content is not a string
     */
    public function __toString(): string
    {
        $this->throwExceptionIfNotString();
        return $this->content;
    }

    /**
     * Whether an offset exists
     *
     * @param mixed $offset
     * @return bool
     * @throws ItemContentException If item content is not an array
     */
    public function offsetExists($offset): bool
    {
        $this->throwExceptionIfNotArray();
        return isset($this->content[$offset]);
    }

    /**
     * Return item content by offset
     *
     * @param mixed $offset
     * @return mixed|null
     * @throws ItemContentException If item content is not an array
     */
    public function offsetGet($offset)
    {
        $this->throwExceptionIfNotArray();
        return isset($this->content[$offset]) ? $this->content[$offset] : null;
    }

    /**
     * Set item content by offset
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws ItemContentException If item content is not an array
     */
    public function offsetSet($offset, $value)
    {
        $this->throwExceptionIfNotArray();
        $this->content[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @param mixed $offset
     * @throws ItemContentException If item content is not an array
     */
    public function offsetUnset($offset)
    {
        $this->throwExceptionIfNotArray();
        unset($this->content[$offset]);
    }
}
