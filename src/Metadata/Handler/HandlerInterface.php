<?php
declare(strict_types=1);

namespace Strata\Data\Metadata\Handler;

use Strata\Data\Metadata\Metadata2;

interface HandlerInterface
{
    /**
     * Does a metadata item exist for ID?
     *
     * @param $id
     * @return bool
     */
    public function has($id): bool;

    /**
     * Return one metadata item based on ID
     *
     * @param $id
     * @return Metadata2
     */
    public function read($id): Metadata2;

    /**
     * Write one metadata item to storage
     *
     * @param Metadata2 $metadata
     * @return mixed
     */
    public function write(Metadata2 $metadata);

    /**
     * Delete one metadata item based on ID
     *
     * @param $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Delete all metadata items
     *
     * @return mixed
     */
    public function deleteAll();

    /**
     * Search for metadata items by attribute
     *
     * @param $attribute
     * @param $keyword
     * @return array
     */
    public function search($attribute, $keyword): array;

}
