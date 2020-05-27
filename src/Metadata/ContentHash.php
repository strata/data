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
 * $metadata = new ContentHash();
 * $metadata->generateContentHash('content');
 * if ($metadata->isChanged()) {
 *     // do something
 * }
 *
 * @package Strata\Data\Metadata
 */
class ContentHash
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
        $currentContentHash = $this->getContentHash();
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
     * @return string
     */
    public function getContentHash(): string
    {
        return $this->contentHash;
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

}
