<?php
declare(strict_types=1);

namespace Strata\Data\Tests;

use PHPUnit\Framework\TestCase;
use Strata\Data\Metadata\Metadata;
use Strata\Data\Metadata\MetadataFactory;
use Strata\Data\Metadata\MetadataRepository;
use Strata\Data\Storage\ArrayStorage;

class MetadataTest extends TestCase
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
        $this->metaDataRepository = new MetadataRepository(new ArrayStorage());
    }

    public function testArrayStorageDataPersistenceWithNoProvidedKey()
    {
        $metaData = $this->metaDataFactory->createNew();
        $metaData->setUrl('https://example.net');
        $metaData->setAttribute('type', 'example_type');

        $this->metaDataRepository->store($metaData);
        $id = $metaData->getId();

        $this->assertTrue($this->metaDataRepository->exists($id));
    }

    public function testArrayStorageDataPersistenceWithProvidedKey()
    {
        $id = rand(0, 999);

        $metaData = $this->metaDataFactory->createNew();
        $metaData->setUrl('https://example.net');
        $metaData->setAttribute('type', 'example_type');
        $metaData->setId($id);

        $this->metaDataRepository->store($metaData);

        $this->assertTrue($this->metaDataRepository->exists($id));
    }

    public function testItemCanBeRetrieved() {
        $id = 36;
        $url = 'https://example.net';

        $metaData = $this->metaDataFactory->createNew();
        $metaData->setUrl($url);
        $metaData->setAttribute('type', 'example_type');
        $metaData->setId($id);

        $this->metaDataRepository->store($metaData);

        unset($metaData);

        $metaData = $this->metaDataRepository->find($id);

        $this->assertInstanceOf(Metadata::class, $metaData);
        $this->assertEquals($url, $metaData->getUrl());
    }

    public function testDeleteItemFromStorage() {
        $id = 34;
        $this->addExampleItemToStorage($id);

        $this->assertTrue($this->metaDataRepository->exists($id));

        $this->metaDataRepository->delete($id);

        $this->assertFalse($this->metaDataRepository->exists($id));
    }

    protected function addExampleItemToStorage($id): Metadata {
        $metaData = $this->metaDataFactory->createNew();
        $metaData->setId($id);
        $metaData->setUrl('https://another-example.co.uk');
        $metaData->setAttribute('type', 'example_type');
        $metaData->setAttributes(['attr1' => 'Purple', 'attr2' => 8973]);
        $metaData->setContentHash('asdfjh38f2£F23f23f23f');

        $this->metaDataRepository->store($metaData);

        return $metaData;
    }

}