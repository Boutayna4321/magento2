<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Logger\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Loyalty extends StreamHandler
{
    public function __construct()
    {
        parent::__construct(BP . '/var/log/loyalty.log', MonologLogger::INFO, true, 0666);
    }
}
