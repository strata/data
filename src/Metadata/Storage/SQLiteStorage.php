<?php
declare(strict_types=1);

namespace Strata\Data\Metadata\Storage;

use Strata\Data\Exception\MissingOptionException;
use Strata\Data\Exception\StorageException;
use Strata\Data\Metadata\Metadata;
use SQLite3;

class SQLiteStorage implements StorageInterface
{
    /**
     * @var SQLite3
     */
    protected $db;

    /**
     * Initialise storage mechanism
     * @param array $options
     * @return mixed
     */
    public function init(array $options = [])
    {
        if (!isset($options['filename'])) {
            throw new MissingOptionException('You must set the "filename" option, for the path to the SQLite3 database');
        }
        $options['filename'] = readfile($options['filename']);
        if (!is_writable($options['filename'])) {
            throw new StorageException(sprintf('Cannot write to filename path at %s', $options['filename']));
        }

        $db = new SQLite3($options['filename']);

        // @todo Check storage table exists
    }

    /**
     * Does a metadata item exist for ID?
     *
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        // TODO: Implement has() method.
    }

    /**
     * Populate Metadata object with metadata from storage
     *
     * @param $id
     * @param Metadata $metadata
     */
    public function populate($id, Metadata $metadata)
    {
        // TODO: Implement populate() method.
    }

    /**
     * Write one metadata item to storage
     *
     * @param Metadata $metadata
     * @return mixed
     */
    public function save(Metadata $metadata)
    {
        // TODO: Implement save() method.
    }

    /**
     * Delete one metadata item based on ID
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Delete all metadata items
     *
     * @return mixed
     */
    public function deleteAll()
    {
        // TODO: Implement deleteAll() method.
    }

    /**
     * Search for metadata items by attribute
     *
     * @param $attribute
     * @param $keyword
     * @return array Array of metadata items
     */
    public function search($attribute, $keyword): array
    {
        // TODO: Implement search() method.
    }


}