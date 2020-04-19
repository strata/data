<?php
declare(strict_types=1);

namespace Strata\Data\Metadata;

use \DateTime;
use Strata\Data\Exception\InvalidMetadataId;

/**
 * Class to store metadata about a piece of content/data synced from an external API
 *
 * @package Strata\Data\Metadata
 */
class Metadata2
{
    /** @var int|string */
    protected $id;

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
     * @param string|int $id
     * @throws InvalidMetadataId
     */
    public function __construct($id)
    {
        $this->created = new DateTime();
        $this->setId($id);
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
     * @return Metadata2 Fluent interface
     * @throws InvalidMetadataId
     */
    public function setId($id): Metadata2
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
     * @return Metadata2 Fluent interface
     */
    public function setUrl(string $url): Metadata2
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
     * @return Metadata2 Fluent interface
     */
    public function setContentHash(string $contentHash): Metadata2
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
     * @return Metadata2 Fluent interface
     */
    public function set($name, $value): Metadata2
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
