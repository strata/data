<?php
declare(strict_types=1);

namespace Strata\Data\Metadata\Storage;

use Strata\Data\Metadata\Metadata;

interface StorageInterface
{
    /**
     * Initialise storage mechanism
     * @param array $options
     * @return mixed
     */
    public function init(array $options = []);

    /**
     * Does a metadata item exist for ID?
     *
     * @param $id
     * @return bool
     */
    public function has($id): bool;

    /**
     * Populate Metadata object with metadata from storage
     *
     * @param $id
     * @param Metadata $metadata
     */
    public function populate($id, Metadata $metadata);

    /**
     * Write one metadata item to storage
     *
     * @param Metadata $metadata
     * @return mixed
     */
    public function save(Metadata $metadata);

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
     * @return array Array of metadata items
     */
    public function search($attribute, $keyword): array;
}
