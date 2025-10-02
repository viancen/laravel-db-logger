<?php

namespace Viancen\LaravelDbLogger\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

class TestLogCommand extends Command
{
    protected $signature = 'test:log
                            {message=Test log via console}
                            {--level=info : Loglevel (emergency|alert|critical|error|warning|notice|info|debug|0..7)}
                            {--exception : Genereer een exception met stacktrace}
                            {--nested : Genereer een nested exception}
                            {--batch=1 : Aantal logs om te genereren}
                            {--demo : Genereer diverse demo logs met verschillende scenarios}';

    protected $description = 'Schrijf testlogs naar de DB-logs-tabel';

    private const ALLOWED = [
        LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR,
        LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG,
    ];

    private const RFC_TO_PSR = [
        0 => LogLevel::EMERGENCY, 1 => LogLevel::ALERT, 2 => LogLevel::CRITICAL,
        3 => LogLevel::ERROR, 4 => LogLevel::WARNING, 5 => LogLevel::NOTICE,
        6 => LogLevel::INFO, 7 => LogLevel::DEBUG,
    ];

    public function handle(): int
    {
        if ($this->option('demo')) {
            return $this->generateDemoLogs();
        }

        $message = (string)$this->argument('message');
        $batch = (int)$this->option('batch');

        for ($i = 0; $i < $batch; $i++) {
            $this->writeLog($message, $i);
        }

        $this->info("✓ {$batch} log(s) geschreven");
        return self::SUCCESS;
    }

    private function writeLog(string $message, int $iteration = 0): void
    {
        $optLevel = strtolower((string)$this->option('level'));

        if (ctype_digit($optLevel) && isset(self::RFC_TO_PSR[(int)$optLevel])) {
            $level = self::RFC_TO_PSR[(int)$optLevel];
        } else {
            $level = $optLevel;
        }

        if (!in_array($level, self::ALLOWED, true)) {
            $this->error("Onbekend loglevel: {$optLevel}");
            $this->line('Gebruik: emergency|alert|critical|error|warning|notice|info|debug of 0..7');
            return;
        }

        $context = [
            'source' => 'TestLogCommand',
            'cli' => true,
            'iteration' => $iteration,
        ];

        if ($this->option('exception')) {
            try {
                if ($this->option('nested')) {
                    $this->triggerNestedExceptions();
                } else {
                    $this->triggerException();
                }
            } catch (\Throwable $e) {
                Log::log($level, $message, array_merge($context, [
                    'exception' => $e,
                ]));
                return;
            }
        }

        Log::log($level, $message, $context);
    }

    private function triggerException(): void
    {
        // Simuleer een realistische call stack
        $this->levelOne();
    }

    private function levelOne(): void
    {
        $this->levelTwo();
    }

    private function levelTwo(): void
    {
        $data = ['user_id' => 123, 'action' => 'process'];
        $this->levelThree($data);
    }

    private function levelThree(array $data): void
    {
        // Simuleer verschillende soorten exceptions
        $exceptions = [
            fn() => throw new \RuntimeException('Database connection failed: timeout after 30 seconds'),
            fn() => throw new \InvalidArgumentException('Invalid UUID format provided: "not-a-uuid"'),
            fn() => throw new \LogicException('Cannot process order in state "cancelled"'),
            fn() => throw new \Exception('File not found: /var/www/storage/uploads/missing.pdf'),
        ];

        $exceptions[array_rand($exceptions)]();
    }

    private function triggerNestedExceptions(): void
    {
        try {
            $this->deepMethod();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to complete operation: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function deepMethod(): void
    {
        try {
            $this->evenDeeperMethod();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Validation failed in deep method',
                0,
                $e
            );
        }
    }

    private function evenDeeperMethod(): void
    {
        throw new \Exception('Original error: Payment gateway returned error code 502');
    }

    private function generateDemoLogs(): int
    {
        $this->info('Genereer diverse demo logs...');
        $this->newLine();

        // 1. Simple info log
        Log::info('User logged in successfully', [
            'user_id' => 'c7e4a3b1-2d5f-4e8a-9c1b-6f3d7e2a8b4c',
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
        ]);
        $this->line('✓ Info log: User login');

        // 2. Warning log
        Log::warning('API rate limit approaching', [
            'current_requests' => 950,
            'limit' => 1000,
            'period' => '1 hour',
            'endpoint' => '/api/v1/users',
        ]);
        $this->line('✓ Warning: Rate limit');

        // 3. Error met exception
        try {
            throw new \RuntimeException('Database query timeout after 30 seconds');
        } catch (\Exception $e) {
            Log::error('Database operation failed', [
                'query' => 'SELECT * FROM orders WHERE status = ?',
                'timeout' => 30,
                'exception' => $e,
            ]);
        }
        $this->line('✓ Error: Database timeout met stacktrace');

        // 4. Critical met nested exception
        try {
            try {
                throw new \Exception('Payment gateway unreachable');
            } catch (\Exception $e) {
                throw new \RuntimeException('Failed to process payment for order #12345', 0, $e);
            }
        } catch (\Exception $e) {
            Log::critical('Payment processing failed', [
                'order_id' => 12345,
                'amount' => 99.99,
                'currency' => 'EUR',
                'exception' => $e,
            ]);
        }
        $this->line('✓ Critical: Payment failure met nested exception');

        // 5. Debug log met veel context
        Log::debug('Cache miss for user profile', [
            'cache_key' => 'user:profile:123',
            'ttl' => 3600,
            'fallback' => 'database',
            'query_time_ms' => 45.3,
            'cache_driver' => 'redis',
        ]);
        $this->line('✓ Debug: Cache miss');

        // 6. Emergency log
        try {
            throw new \ErrorException('Out of memory: Allowed memory size of 128 MB exhausted');
        } catch (\Exception $e) {
            Log::emergency('System out of memory', [
                'memory_limit' => '128M',
                'memory_used' => '127.8M',
                'process' => 'report-generator',
                'exception' => $e,
            ]);
        }
        $this->line('✓ Emergency: Out of memory');

        // 7. Notice log
        Log::notice('Deprecated API endpoint used', [
            'endpoint' => '/api/v1/legacy/users',
            'deprecated_since' => '2024-01-01',
            'alternative' => '/api/v2/users',
            'caller_ip' => '10.0.0.50',
        ]);
        $this->line('✓ Notice: Deprecated API');

        // 8. Alert log
        Log::alert('Failed login attempts exceeded threshold', [
            'ip_address' => '123.45.67.89',
            'attempts' => 10,
            'threshold' => 5,
            'lockout_duration' => '15 minutes',
            'account' => 'admin@example.com',
        ]);
        $this->line('✓ Alert: Multiple failed logins');

        // 9. Error met verschillende exception types
        $exceptionTypes = [
            new \InvalidArgumentException('Invalid email format: "not-an-email"'),
            new \LogicException('Cannot delete user with active subscriptions'),
            new \UnexpectedValueException('Expected array, got string'),
        ];

        foreach ($exceptionTypes as $exc) {
            Log::error('Validation error: ' . $exc->getMessage(), [
                'exception' => $exc,
                'input' => ['email' => 'not-an-email'],
            ]);
        }
        $this->line('✓ Errors: Verschillende exception types');

        // 10. Queue job failure
        try {
            throw new \Exception('External API returned 503 Service Unavailable');
        } catch (\Exception $e) {
            Log::error('Queue job failed', [
                'job' => 'SendEmailNotification',
                'attempts' => 3,
                'max_attempts' => 3,
                'queue' => 'emails',
                'exception' => $e,
            ]);
        }
        $this->line('✓ Error: Queue job failure');

        $this->newLine();
        $this->info('✓ Demo logs succesvol gegenereerd!');
        $this->line('Bekijk de logs in het dashboard');

        return self::SUCCESS;
    }
}
