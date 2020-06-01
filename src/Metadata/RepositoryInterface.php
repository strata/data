<?php
declare(strict_types=1);

namespace Strata\Data\Metadata;

interface RepositoryInterface
{
    public function getTableSetupScript(string $type): string;
}