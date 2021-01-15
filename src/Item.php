<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Decode\DecoderStrategy;
use Strata\Data\Exception\DecoderException;
use Strata\Data\Exception\ItemContentException;
use Strata\Data\Helper\ContentHasher;
use DateTime;

/**
 * Cacheable item returned from an API request
 *
 * Usage:
 * use Strata\Data\Decode\DecoderStrategy;
 * use Strata\Data\Decode\JsonDecoder;
 *
 * $decoder = new DecoderStrategy(new JsonDecoder());
 * $item = new Item($uri, $data, $decoder);
 *
 * @package Strata\Data\Data
 */
class Item
{
    private string $identifier;
    private string $contentHash;
    private bool $isSuccessful;
    private \DateTime $updated;
    private $content;
    private array $meta;
    private ContentHasher $hasher;

    /**
     * Constructor
     *
     * @param string $identifier
     * @param null $content
     * @param ?DecoderStrategy $decoder Decoder to transform content
     * @throws ItemContentException
     */
    public function __construct(string $identifier, $content = null, ?DecoderStrategy $decoder = null)
    {
        $this->hasher = new ContentHasher();

        $this->identifier = $identifier;
        $this->updated = new DateTime();

        if ($content !== null) {
            $this->setContent($content, $decoder);
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set content
     *
     * Content hash is automatically set the first time content is set on an Item (since this is likely to be a string)
     * If a decoder is passed, then content is transformed to the required format
     *
     * @param array|string $content
     * @param ?DecoderStrategy $decoder Decoder to transform content
     * @throws ItemContentException
     * @throws DecoderException
     */
    public function setContent(string $content, ?DecoderStrategy $decoder)
    {
        if (!is_array($content) && (!is_string($content))) {
            throw new ItemContentException(sprintf('Item content is invalid. Require string or array, %s detected', gettype($content)));
        }

        $this->content = $content;

        if (empty($this->contentHash)) {
            $this->generateContentHash();
        }

        if ($decoder !== null) {
            $decoder->decode($this);
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

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function generateContentHash()
    {
        if (empty($this->getContent())) {
            throw new ItemContentException('Cannot set content hash until content is set on this Item');
        }
        $this->contentHash = $this->hasher->hash($this->__toString());
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    /**
     * Return whether new content is different to content already stored against this Item
     *
     * Useful if you cache items and check them again later
     *
     * @param string $newContent
     * @return bool
     */
    public function isChanged(string $newContent): bool
    {
        return $this->hasher->hasContentChanged($this->getContentHash(), $newContent);
    }

    /**
     * Return a string representation of content
     *
     * If content is a data array, uses print_r to return string representation
     * If content is empty, returns an empty string
     * @return string
     */
    public function __toString(): string
    {
        if (is_string($this->getContent())) {
            return (string) $this->getContent();
        }

        if (is_array($this->getContent())) {
            return print_r($this->getContent(), true);
        }

        return '';
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
     * Cleanup before serialization
     * @return string[]
     */
    public function __sleep(): array
    {
        unset($this->hasher);
        return ['identifier', 'contentHash', 'updated', 'content', 'meta'];
    }

    /**
     * Restart after unserialization
     */
    public function __wake(): void
    {
        $this->hasher = new ContentHasher();
    }

}
