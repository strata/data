<?php
declare(strict_types=1);

namespace Strata\Data\Event;

class DecodeEvent extends RequestEventAbstract
{
    const NAME = 'data.request.decode';

    private $decodedData;

    public function __construct($decodedData, string $requestId, string $uri, array $context = [])
    {
        $this->decodedData = $decodedData;
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
