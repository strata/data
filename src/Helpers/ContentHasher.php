<?php
declare(strict_types=1);

namespace Strata\Data\Helpers;

use Strata\Data\Exception\InvalidHashAlgorithm;

/**
 * A simple content hash class to generate hashes based on strings.
 * Includes comparision method to determine if content has changed
 *
 */
class ContentHasher
{
    /** @var string */
    const DEFAULT_ALGORITHM = 'sha256';

    /** @var string */
    protected $algorithm;

    /**
     * ContentHasher constructor.
     * @param string|null $algorithm
     * @throws \Strata\Data\Exception\InvalidHashAlgorithm
     */
    public function __construct(string $algorithm = null)
    {
        $this->setHashAlgorithm($algorithm);
    }

    /**
     * Set the hash algorithm
     *
     * @param string|null $algorithm Algorithm to use when using hash() function
     * @throws InvalidHashAlgorithm
     * @see https://www.php.net/hash
     */
    public function setHashAlgorithm(string $algorithm = null): void
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
     * Determine whether content has changed based on the original hash
     *
     * @param string $originalHash
     * @param string $content
     * @return bool
     */
    public function hasContentChanged(string $originalHash, string $content): bool
    {
        $newContentHash = $this->hash($content);

        if ($newContentHash === $originalHash) {
            return false;
        }

        return true;
    }

}
