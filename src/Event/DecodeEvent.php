<?php

declare(strict_types=1);

namespace Strata\Data\Event;

class DecodeEvent extends RequestEventAbstract
{
    const NAME = 'data.request.decode';

    public function __construct(private $decodedData, string $requestId, string $uri, array $context = [])
    {
        parent::__construct($requestId, $uri, $context);
    }

    /**
     * Return decoded data
     *
     * @return mixed
     */
    public function getDecodedData()
    {
        return $this->decodedData;
    }
}
