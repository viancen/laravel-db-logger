<?php
namespace Viancen\LaravelDbLogger\Logging;

use Monolog\Logger;

class CustomizeMonolog
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new RequestContextProcessor());
    }
}
