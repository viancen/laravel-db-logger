<?php

namespace Viancen\LaravelDbLogger\Logging;

use Illuminate\Support\Facades\DB;
use Monolog\Level;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    public function __construct(Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        // Debug: constructor aangeroepen
//        file_put_contents(storage_path('logs/db-handler-debug.txt'),
//            date('H:i:s') . " - Constructor called\n", FILE_APPEND);
    }

    protected function write(LogRecord $record): void
    {
        // Debug: write aangeroepen
//        file_put_contents(storage_path('logs/db-handler-debug.txt'),
//            date('H:i:s') . " - WRITE CALLED: {$record->message}\n", FILE_APPEND);

        try {
            $payload = [
                'level' => $this->mapLevel($record->level),
                'channel' => $record->channel,
                'message' => $record->message,
                'context' => json_encode($record->context, JSON_UNESCAPED_UNICODE),
                'extra' => json_encode($record->extra, JSON_UNESCAPED_UNICODE),
                'request_id' => $record->extra['request_id'] ?? null,
                'ip_address' => $record->extra['ip'] ?? null,
                'user_agent' => $record->extra['user_agent'] ?? null,
                'user_id' => $record->extra['user_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table(config('db-logger.table', 'db_logger'))->insert($payload);

//            file_put_contents(storage_path('logs/db-handler-debug.txt'),
//                date('H:i:s') . " - INSERT SUCCESS\n", FILE_APPEND);

        } catch (\Throwable $e) {
//            file_put_contents(storage_path('logs/db-handler-debug.txt'),
//                date('H:i:s') . " - ERROR: {$e->getMessage()}\n", FILE_APPEND);
            error_log('[DatabaseLogHandler] Error: ' . $e->getMessage());
        }
    }

    private function mapLevel(Level $level): int
    {
        return match ($level) {
            Level::Emergency => 0,
            Level::Alert => 1,
            Level::Critical => 2,
            Level::Error => 3,
            Level::Warning => 4,
            Level::Notice => 5,
            Level::Info => 6,
            Level::Debug => 7,
        };
    }
}
