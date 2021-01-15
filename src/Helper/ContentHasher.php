<?php
declare(strict_types=1);

namespace Strata\Data\Helper;

/**
 * A simple content hash helper class to generate hashes based on strings
 */
class ContentHasher
{
    /** @var string */
    const HASH_ALGORITHM = 'sha256';

    /**
     * Return a hash based on passed content
     *
     * @param string $content
     * @return string Hash
     */
    public static function hash(string $content): string
    {
        return hash(self::HASH_ALGORITHM, $content);
    }

    /**
     * Determine whether content has changed based on the original hash
     *
     * @param string $originalHash
     * @param string $content
     * @return bool
     */
    public static function hasContentChanged(string $originalHash, string $content): bool
    {
        $newContentHash = self::hash($content);

        if ($newContentHash === $originalHash) {
            return false;
        }

        return true;
    }

}
