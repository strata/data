<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Metadata\MetadataFactory;
use Strata\Data\Metadata\MetadataRepository;
use Strata\Data\Storage\SQLiteStorage;

class SqliteMetadataTest extends TestCase
{

    /**
     * @var MetadataFactory
     */
    protected $metaDataFactory;
    /**
     * @var MetadataRepository
     */
    protected $metaDataRepository;

    /**
     * Sets up the properties before the next test runs
     */
    protected function setUp(): void
    {
        $this->metaDataFactory = new MetadataFactory();

        $sqliteStorage = new SQLiteStorage();
        $sqliteStorage->init(['filename' => __DIR__ . '/database.sqlite']);
        $this->metaDataRepository = new MetadataRepository($sqliteStorage);
    }

    public function testSqliteStorageDataPersistenceWithNoProvidedKey()
    {
        $id = 4;

        $metaData = $this->metaDataFactory->createNew();
        $metaData->setUrl('https://example.net');
        $metaData->setAttribute('type', 'example_type');
        $metaData->setId($id);

        $this->metaDataRepository->store($metaData);

        $this->assertTrue($this->metaDataRepository->exists($id));
    }

}