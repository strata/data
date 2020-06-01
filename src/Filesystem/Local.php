<?php
declare(strict_types=1);

namespace Strata\Data\Filesystem;

use Strata\Data\Permissions;
use League\Flysystem\Adapter\Local as FlysystemLocal;
use Strata\Data\Collection\ListInterface;

class Local extends FilesystemAbstract
{

    public function __construct(string $baseUri, Permissions $permissions = null)
    {
        $this->setBaseUri($baseUri);
        $this->setPermissions($permissions);
        $this->setAdapter(new FlysystemLocal($this->getBaseUri()));
    }

    /**
     * Return current item
     * @return string
     */
    public function current(): string
    {
        return $this->collection[$this->position];
    }
}
