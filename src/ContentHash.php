<?php
declare(strict_types=1);

namespace Strata\Data;

use Strata\Data\Exception\InvalidHashAlgorithm;

class ContentHash
{
    /** @var string */
    const DEFAULT_ALGORITHM = 'sha256';

    /** @var string */
    protected $algorithm;

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
     * Compare two hashes to see if they are identical
     *
     * @param string $hash1
     * @param string $hash2
     * @return bool
     */
    public function compare(string $hash1, string $hash2): bool
    {
        return ($hash1 === $hash2);
    }
}
