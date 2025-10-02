<?php

namespace Viancen\LaravelDbLogger\Logging;

use Illuminate\Support\Str;
use Monolog\LogRecord;

class RequestContextProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        // Request-id (neem header X-Request-Id of genereer)
        $requestId = request()?->headers->get('X-Request-Id')
            ?? (php_sapi_name() === 'cli' ? null : (string) Str::uuid());

        $extra = [
            'request_id' => $requestId,
            'ip'         => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'user_id'    => auth()->check() ? (string) auth()->id() : null,
        ];

        // Merge met bestaande extra data
        return $record->with(extra: array_merge($record->extra, $extra));
    }
}
