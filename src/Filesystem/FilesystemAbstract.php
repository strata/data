<?php
declare(strict_types=1);

namespace Strata\Data\Filesystem;

use League\Flysystem\AdapterInterface;
use Strata\Data\Collection\FilesystemList;
use Strata\Data\Collection\ListInterface;
use Strata\Data\DataInterface;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\DataNotFoundException;
use Strata\Data\Permissions;
use Strata\Data\Query;
use Strata\Data\Traits\BaseUriTrait;
use Strata\Data\Traits\CheckPermissionsTrait;
use League\Flysystem\Filesystem;

/**
 * Core functionality for getting data from filesystem
 *
 * Use via child classes that extend this abstract class
 * @package Strata\Data\Filesystem
 */
abstract class FilesystemAbstract implements DataInterface
{
    use CheckPermissionsTrait, BaseUriTrait;

    /** @var Filesystem */
    protected $filesystem;

    /** @var AdapterInterface */
    protected $adapter;

    /**
     * Set adapater
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Return adapter
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Set filesystem object explicitly
     *
     * Under normal circumstances filesystem is lazy loaded via getFilesystem() method
     * @param Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Setup and return filesystem object
     * @param array $config
     * @return Filesystem
     */
    public function getFilesystem(array $config = null): Filesystem
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }
        $this->filesystem = new Filesystem($this->getAdapter(), $config);
        return $this->filesystem;
    }

    /**
     * Return URI for current data request (base URI + endpoint)
     *
     * Expands URI into the absolute path and returns DataNotFoundException if not found
     * @return string
     * @throws BaseUriException
     * @throws DataNotFoundException
     */
    public function getUri(): string
    {
        $uri = $this->getBaseUri();
        if (!empty($this->getEndpoint())) {
            $uri .= DIRECTORY_SEPARATOR . $this->getEndpoint();
        }
        $absoluteUri = realpath($uri);
        if ($absoluteUri === false) {
            throw new DataNotFoundException(sprintf('Filepath does not exist at "%s"', $uri));
        }

        return (string) $absoluteUri;
    }

    /** EDIT FROM HERE */

    /**
     * Return one item
     * @param $filename Filename to return item
     * @param array $requestOptions Array of options to pass to the request
     * @return Content for the item
     * @throws DataNotFoundException If data not found
     */
    public function getOne($filename, array $requestOptions = []): string
    {
        $this->permissionRead();
        $this->setEndpoint((string) $filename);
        if (!$this->getFilesystem()->has($this->getEndpoint())) {
            throw new DataNotFoundException(sprintf('File "%s" not found at path "%s"', $this->getEndpoint(), $this->getUri()));
        }

        $data = $this->getFilesystem()->read($this->getEndpoint());
        if ($data === false) {
            throw new DataNotFoundException(sprintf('Cannot load file "%s"', $uri));
        }

        // Can we set metadata now? For path & last updated time?
        return $data;
    }

    /**
     * Set the current endpoint to use with the data request
     * @param string $endpoint
     * @return DataInterface
     */
    public function from(string $endpoint): DataInterface
    {
        $this->setEndpoint($endpoint);
        return $this;
    }

    /**
     * Return a list of items
     * @param Query $query Query object to generate the list
     * @param array $requestOptions Array of options to pass to the request
     * @return ListAbstract
     */
    public function getList(Query $query = null, array $requestOptions = []): ListInterface
    {
        $this->permissionRead();
        $this->setEndpoint((string) './');
        if (!$this->getFilesystem()->has($this->getEndpoint())) {
            throw new DataNotFoundException(sprintf('Folder "%s" not found at path "%s"', $this->getEndpoint(), $this->getUri()));
        }

        $data = [];

        $recursive = false;
        if ($requestOptions['recursive']) {
            $recursive = true;
        }
        $contents = $this->getFilesystem()->listContents($this->getEndpoint(), $recursive);

        foreach ($contents as $object) {
            //echo $object['basename'].' is located at '.$object['path'].' and is a '.$object['type'];
            $data[] = $object['contents'];
        }

        // @todo data filter
        // @todo metadata
        // @todo only get files that match pattern (file extension)

        return new FilesystemList($data);
    }

    /**
     * Whether the last response has any results
     * @return bool
     */
    public function hasResults(): bool
    {
    }
}
