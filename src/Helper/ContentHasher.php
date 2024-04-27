<?php

declare(strict_types=1);

namespace Strata\Data\Helper;

use InvalidArgumentException;

/**
 * A simple content hasher helper class to generate a short hash based on the contents of a string or array
 */
class ContentHasher
{
    /** @var string */
    const HASH_ALGORITHM = 'sha256';

    /**
     * Return a hash based on passed content
     *
     * @param array|string $content
     * @return string Hash
     * @throws InvalidArgumentException
     */
    public static function hash(array|string $content): string
    {
        $content = self::normalise($content);
        return hash(self::HASH_ALGORITHM, $content);
    }

    /**
     * Determine whether content has changed based on the original hash
     *
     * @param array|string $originalHash
     * @param array|string $content
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function hasContentChanged(array|string $originalHash, array|string $content): bool
    {
        $content = self::normalise($content);
        $newContentHash = self::hash($content);

        if ($newContentHash === $originalHash) {
            return false;
        }

        return true;
    }

    /**
     * Normalise content so it is a string and can be used to create a hash
     *
     * @param array|string $content
     * @return string
     * @throws InvalidArgumentException
     */
    private static function normalise(array|string $content): string
    {
        if (is_string($content)) {
            return $content;
        } elseif (is_array($content)) {
            return print_r($content, true);
        }
    }
}
