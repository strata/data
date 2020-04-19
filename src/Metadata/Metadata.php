<?php
declare(strict_types=1);

namespace Strata\Data\Metadata;

use DateTime;
use Strata\Data\Exception\InvalidHashAlgorithm;
use Strata\Data\Exception\InvalidMetadataId;

/**
 * A simple content hash class to help check whether content has been updated
 *
 * Usage:
 *
 *
 * $metadata = new Metadata('id', 'content');
 * if ($metadata->isChanged()) {
 *     // do something
 * }
 *
 * Q. How do I load this from storage????
 *
 *
 * OLD
 *
 * // Get from cache
 * $content = unserialize($data);
 * if ($content === false) {
 *     $content = new ContentHash();
 * }
 *
 * // Check if content has changed
 * if ($content->isChanged('id', 'content') {
 *     // do something
 * }
 *
 * // Save to cache so we can compare content over time
 * $data = serialize($content);
 *
 * @package Strata\Data
 */

/**
 * Class to store metadata about a piece of content/data synced from an external API
 *
 * @package Strata\Data\Metadata
 */
class Metadata
{
    /** @var string */
    const DEFAULT_ALGORITHM = 'sha256';

    /** @var int */
    const STATUS_NEW = 1;
    const STATUS_CHANGED = 2;
    const STATUS_NOT_CHANGED = 3;
    const STATUS_DELETED = 4;

    /** @var string */
    protected $algorithm;

    /** @var int|string */
    protected $id;

    /**
     * Status of content
     *
     * We assume content is deleted, as metadata is updated if it exists in
     * API it should be marked as updated or new
     *
     * @var int
     */
    protected $status = self::STATUS_DELETED;

    /** @var string */
    protected $url;

    /** @var string */
    protected $contentHash;

    /** @var DateTime */
    protected $created;

    /** @var DateTime */
    protected $updated;

    /** @var array */
    protected $attributes;

    /**
     * Constructor
     *
     * @param int|string $id ID to reference this content
     * @param string $content Content to detect whether this is new, changed or deleted
     * @throws InvalidMetadataId
     */
    public function __construct($id, string $content = null)
    {
        $this->created = new DateTime();
        $this->setId($id)
             ->generateContentHash($content);
    }

    /**
     * Set the hash algorithm
     *
     * @param string|null $algorithm Algorithm to use when using hash() function
     * @throws InvalidHashAlgorithm
     * @return Metadata Fluent interface
     * @see https://www.php.net/hash
     */
    public function setHashAlgorithm(string $algorithm = null): Metadata
    {
        if (null !== $algorithm) {
            $this->algorithm = $algorithm;
        } else {
            $this->algorithm = self::DEFAULT_ALGORITHM;
        }

        if (!in_array($this->algorithm, hash_algos())) {
            throw new InvalidHashAlgorithm(sprintf('Hash algorithm %s not found on your system', $this->algorithm));
        }

        return $this;
    }

    /**
     * Return a hash based on passed content
     *
     * @param string $content
     * @return string Hash
     */
    public function hash(string $content): string
    {
        return hash($this->algorithm, $content);
    }

    /**
     * if ($metadata->isChanged($content)) { }
     *
     * @param string $content
     * @return Metadata Fluent interface
     */
    public function generateContentHash(string $content): Metadata
    {
        $currentContentHash = $this->contentHash;
        $this->contentHash = $this->hash($content);

        // Detect changes
        if (empty($currentContentHash)) {
            $this->status = self::STATUS_NEW;
        }

        if ($this->hash($content) === $this->content[$id]) {
            $this->status = self::STATUS_NOT_CHANGED;
        } else {
            $this->status = self::STATUS_CHANGED;
        }

        return $this;
    }

    /**
     * Return status of content
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Is the content new?
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return ($this->status === self::STATUS_NEW);
    }

    /**
     * Whether the content has changed?
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return ($this->status === self::STATUS_CHANGED);
    }

    /**
     * Whether the content has not changed since the last import
     *
     * @return bool
     */
    public function notChanged(): bool
    {
        return ($this->status === self::STATUS_NOT_CHANGED);
    }

    /**
     * Whether the content is deleted?
     *
     * Please note you should only check this after a full import otherwise it could incorrectly report as deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return ($this->status === self::STATUS_DELETED);
    }

    /**
     * Validate whether the identifier argument is OK
     *
     * @param $id
     * @throws InvalidMetadataId
     */
    protected function validateIdentifier($id)
    {
        if (!is_string($id) && !is_int($id)) {
            throw new InvalidMetadataId(sprintf('$id argument must be string or integer, %s passed', gettype($id)));
        }
    }

    /**
     * Return ID
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int|string $id
     * @return Metadata Fluent interface
     * @throws InvalidMetadataId
     */
    public function setId($id): Metadata
    {
        $this->validateIdentifier($id);
        $this->id = $id;
        $this->update();

        return $this;
    }

    /**
     * Return created datetime
     *
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * Return last updated datetime
     *
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * Update last updated with current DateTime
     */
    public function update(): void
    {
        $this->setUpdated(new DateTime());
    }

    /**
     * Return URL of data source
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set URL of data source
     *
     * @param string $url
     * @return Metadata Fluent interface
     */
    public function setUrl(string $url): Metadata
    {
        $this->url = $url;
        $this->update();

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    /**
     *
     *
     * @param string $contentHash
     * @return Metadata Fluent interface
     */
    public function setContentHash(string $contentHash): Metadata
    {
        $this->contentHash = $contentHash;
        $this->update();

        return $this;
    }

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set one attribute
     *
     * @param $name
     * @param $value
     * @return Metadata Fluent interface
     */
    public function set($name, $value): Metadata
    {
        $this->attributes[$name] = $value;
        $this->update();

        return $this;
    }

    /**
     * Does an attribute exist?
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name)
    {
        return (isset($this->attributes[$name]));
    }

    /**
     * Return attribute, or null if not set
     *
     * @param string $name
     * @return mixed|null
     */
    public function get(string $name)
    {
        if ($this->has($name)) {
            return $this->attributes[$name];
        }
        return null;
    }

}
