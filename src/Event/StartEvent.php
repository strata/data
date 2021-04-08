<?php

declare(strict_types=1);

namespace Strata\Data\Event;

class StartEvent extends RequestEventAbstract
{
    const NAME = 'data.request.start';
}
