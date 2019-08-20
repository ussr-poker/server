<?php
declare(strict_types=1);

namespace App\Support;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger
{
    private static ?\Monolog\Logger $logger = null;

    public static function getLogger(): \Monolog\Logger
    {
        if (null === static::$logger) {
            $handler = new StreamHandler('php://stdout');
            $handler->setFormatter(new LineFormatter(null, null, false, true));

            static::$logger = new \Monolog\Logger(
                'ussr_poker',
                [
                    $handler
                ],
                [

                ]
            );
        }

        return static::$logger;
    }
}