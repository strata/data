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
     * @param string|array $content
     * @return string Hash
     * @throws InvalidArgumentException
     */
    public static function hash($content): string
    {
        $content = self::normalise($content);
        return hash(self::HASH_ALGORITHM, $content);
    }

    /**
     * Determine whether content has changed based on the original hash
     *
     * @param string|array $originalHash
     * @param string $content
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function hasContentChanged(string $originalHash, $content): bool
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
     * @param $content
     * @return string
     * @throws InvalidArgumentException
     */
    private static function normalise($content): string
    {
        if (is_string($content)) {
            return $content;
        } elseif (is_array($content)) {
            return print_r($content, true);
        } else {
            throw new InvalidArgumentException(sprintf('$content argument must be a string or array, \'%s\' passed', gettype($content)));
        }
    }
}
