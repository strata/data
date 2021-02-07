<?php
declare(strict_types=1);

namespace Strata\Data\Event;

use Strata\Data\Model\Response;
use Symfony\Contracts\EventDispatcher\Event;

abstract class ResponseEventAbstract extends Event
{
    private Response $response;

    public function __construct(Response $response, array $context = [])
    {
        $this->response = $response;
        $this->context = $context;
    }

    /**
     * Return response object
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Return array of contextual info
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

}
