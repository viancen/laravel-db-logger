<?php

namespace Viancen\LaravelDbLogger\Logging;

use Monolog\Logger;
use Monolog\Level;

class CreateDatabaseLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('database');

        // Add de database handler
        $handler = new DatabaseLogHandler(Level::Debug, true);
        $logger->pushHandler($handler);

        // Add processor voor request context
        $logger->pushProcessor(new RequestContextProcessor());

        return $logger;
    }
}
