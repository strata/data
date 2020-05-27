<?php
declare(strict_types=1);

namespace Strata\Data\Metadata;

use \DateTime;
use Strata\Data\Exception\InvalidMetadataId;
use Strata\Data\Metadata\Storage\StorageInterface;

/**
 * Class to store metadata about an item of data
 *
 * @package Strata\Data\Metadata
 */
class Metadata
{
    /** @var int|string */
    protected $id;

    /** @var string */
    protected $url;

    /** @var ContentHash */
    protected $contentHash;

    /** @var DateTime */
    protected $created;

    /** @var DateTime */
    protected $updated;

    /** @var array */
    protected $attributes;

    /** @var StorageInterface */
    protected $storage;

    /**
     * Constructor
     *
     * @param string|int $id
     * @param StorageInterface $storage
     * @throws InvalidMetadataId
     */
    public function __construct($id, StorageInterface $storage)
    {
        $this->storage = $storage;

        // Read existing metadata
        if ($storage->has($id)) {
            $storage->populate($id, $this);
            return;
        }

        // Create new metadata
        $this->created = new DateTime();
        $this->setId($id);
    }

    /**
     * Save metadata to storage
     */
    public function save()
    {
        $this->storage->save($this);
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
     * @return ContentHash
     */
    public function getContentHash(): ContentHash
    {
        return $this->contentHash;
    }

    /**
     * Generate content hash for this item, which helps us work out whether data has been updated
     *
     * @param string $content
     * @return Metadata Fluent interface
     */
    public function generateContentHash(string $content): Metadata
    {
        $this->contentHash->generateContentHash($content);
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
