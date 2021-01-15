<?php
declare(strict_types=1);

namespace Strata\Data;

class Version
{
    const VERSION = '0.8.0';

    /** @var string User agent for HTTP requests */
    const USER_AGENT = 'Strata/' . self::VERSION . ' (https://github.com/strata/data)';
}
