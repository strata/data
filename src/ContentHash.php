<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Exception\InvalidHashAlgorithm;
use Strata\Data\Exception\InvalidHashIdentifier;

/**
 * A simple content hash class to help check whether content has been updated
 *
 * @package Strata\Data
 */
class ContentHash
{
    /** @var string */
    const DEFAULT_ALGORITHM = 'sha256';

    /** @var string */
    protected $algorithm;

    /**
     * Array of ID => Content hashes
     *
     * @var array
     */
    protected $content = [];

    /**
     * Constructor
     *
     * @param string|null $algorithm Algorithm to use when using hash() function
     * @throws InvalidHashAlgorithm
     * @see https://www.php.net/hash
     */
    public function __construct(string $algorithm = null)
    {
        if (null !== $algorithm) {
            $this->algorithm = $algorithm;
        } else {
            $this->algorithm = self::DEFAULT_ALGORITHM;
        }

        if (!in_array($this->algorithm, hash_algos())) {
            throw new InvalidHashAlgorithm(sprintf('Hash algorithm %s not found on your system', $this->algorithm));
        }
    }

    public function __sleep()
    {
        // TODO: Implement __sleep() method.
    }

    public function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }

    /**
     * Compare whether the content is new or updated
     *
     * @param string|int $id Identifier for this content
     * @param string $content
     * @return bool Whether the content is new or updated
     * @throws InvalidHashIdentifier
     */
    public function isChanged($id, string $content): bool
    {
        $this->validateIdentifier($id);

        // Check whether content exists and is identical
        if (isset($this->content[$id]) && ($this->hash($content) === $this->content[$id])) {
            return false;
        }

        // Otherwise it's new or changed
        $this->add($id, $content);
        return true;
    }

    /**
     * Add or replace content
     *
     * @param string|int $id Identifier for this content
     * @param string $content Content
     * @throws InvalidHashIdentifier
     */
    public function add($id, string $content)
    {
        $this->validateIdentifier($id);
        $this->content[$id] = $this->hash($content);
    }

    /**
     * Remove content
     *
     * @param $id
     * @throws InvalidHashIdentifier
     */
    public function remove($id)
    {
        $this->validateIdentifier($id);
        if (isset($this->content[$id])) {
            unset($this->content[$id]);
        }
    }

    /**
     * Validate whether the identifier argument is OK
     *
     * @param $id
     * @throws InvalidHashIdentifier
     */
    protected function validateIdentifier($id)
    {
        static $results = [];
        if (isset($results[$id])) {
            return;
        }

        if (!is_string($id) && !is_int($id)) {
            throw new InvalidHashIdentifier(sprintf('$id argument must be string or integer, %s passed', gettype($id)));
        }

        $results[$id] = true;
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

}
