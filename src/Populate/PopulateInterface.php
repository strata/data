<?php
declare(strict_types=1);

namespace Strata\Data\Populate;

use Strata\Data\Model\Response;

interface PopulateInterface
{
    /**
     * Populate raw data into a response
     *
     * @param Response $response
     * @return Response
     */
    public function populate(Response $response): Response;
}