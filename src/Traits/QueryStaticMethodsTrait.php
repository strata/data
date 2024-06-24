<?php

declare(strict_types=1);

namespace Strata\Data\Traits;

trait QueryStaticMethodsTrait
{
    /**
     * Static method to create a new query
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Static method to create a new query with a URI
     * @param string $uri
     * @return self
     */
    public static function uri(string $uri): self
    {
        $query = new self();
        $query->setUri($uri);
        return $query;
    }
}
