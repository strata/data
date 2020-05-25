<?php
declare(strict_types=1);

namespace Strata\Data\Filter;

interface FilterInterface
{
    /**
     * Filter data
     * @param string $data
     * @return mixed
     */
    public function filter(string $data);
}
