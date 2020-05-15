<?php
declare(strict_types=1);

namespace Strata\Data\Filesystem;

use Strata\Data\Collection\FilesystemList;
use Strata\Data\Collection\ListInterface;
use Strata\Data\DataInterface;
use Strata\Data\Exception\BaseUriException;
use Strata\Data\Exception\DataNotFoundException;
use Strata\Data\Permissions;
use Strata\Data\Query;
use Strata\Data\Traits\BaseUriTrait;
use Strata\Data\Traits\CheckPermissionsTrait;

/**
 * Core functionality for getting data from filesystem
 *
 * Use via child classes that extend this abstract class
 * @package Strata\Data\Filesystem
 */
abstract class FilesystemAbstract implements DataInterface
{
    use CheckPermissionsTrait, BaseUriTrait;

    /**
     * Constructor
     * @param string $baseUri API base URI
     * @param Permissions $permissions (if not passed, default = read-only)
     */
    public function __construct(string $baseUri, Permissions $permissions = null)
    {
        $this->setBaseUri($baseUri);

        if ($permissions instanceof Permissions) {
            $this->setPermissions($permissions);

        } else {
            $this->setPermissions(new Permissions(Permissions::READ));
        }
    }

    /**
     * Load file and return data in a usable format
     *
     * E.g. parse CSV to an array, parse Markdown to HTML
     *
     * @param string $filepath Path to file
     * @return mixed Data loaded from file
     */
    abstract public function loadFile(string $filepath);

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

    /**
     * Return one item
     * @param $identifier Filename to return item
     * @param array $requestOptions Array of options to pass to the request
     * @return Content for the item
     * @throws DataNotFoundException If data not found
     */
    public function getOne($identifier, array $requestOptions = []): string
    {
        $this->permissionRead();

        $this->setEndpoint((string) $identifier);
        $uri = $this->getUri();
        if (!file_exists($uri)) {
            throw new DataNotFoundException(sprintf('File "%s" not found', $identifier));
        }

        return $this->loadFile($uri);
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
        $uri = $this->getUri();
        if (!is_dir($uri)) {
            throw new DataNotFoundException(sprintf('Filepath "%s" is not a folder, cannot list data', $uri));
        }

        $data = [];

        // @todo only get files that match pattern (file extension)
        // @todo convert data on load (e.g. markdown, CSV)
        // @todo support YAML front matter in markdown?

        // Recursive (all levels)
        if (isset($requestOptions['recursive']) && $requestOptions['recursive'] === true) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uri));
            $iterator->rewind();
            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    $data[] = $this->loadFile($iterator->key());
                }
                $iterator->next();
            }

            return new FilesystemList($data);
        }

        // Single-level
        $iterator = new \DirectoryIterator($uri);
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $data[] = $this->loadFile($fileinfo->getPathname());
            }
        }

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