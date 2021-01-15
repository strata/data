<?php
declare(strict_types=1);

namespace Strata\Data\Collection;

class MarkdownList extends ListAbstract
{

    /**
     * Add one item to the list collection
     * @param $item
     * @return ListInterface
     */
    public function addItem($item): ListInterface
    {
        $this->collection[] = $item;
        return $this;
    }

    /**
     * Return current item
     * @return mixed
     */
    public function current(): string
    {
        return $this->collection[$this->position];
    }
}
