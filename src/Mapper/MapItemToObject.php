<?php

declare(strict_types=1);

namespace Strata\Data\Mapper;

class MapItemToObject extends MapItemAbstract implements MapperInterface
{

    /**
     * MapItem constructor
     *
     * @param string $className
     * @param array|MappingStrategy $strategy Array of mapping property paths, or MappingStrategy object
     */
    public function __construct(string $className, $strategy)
    {
        $this->setClassName($className);

        if (is_array($strategy)) {
            $this->setStrategy(new MappingStrategy($strategy));
        }
        if ($strategy instanceof MappingStrategy) {
            $this->setStrategy($strategy);
        }
    }

}
