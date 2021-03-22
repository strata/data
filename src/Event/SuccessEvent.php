<?php
declare(strict_types=1);

namespace Strata\Data\Event;

class SuccessEvent extends RequestEventAbstract
{
    const NAME = 'data.request.success';
}
