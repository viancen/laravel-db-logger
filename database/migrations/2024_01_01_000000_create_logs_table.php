<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('db-logger.table', 'db_logger'), function (Blueprint $table) {
            $table->id();
            $table->smallInteger('level')->index();
            $table->string('channel', 64)->default('app')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('extra')->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('user_id')->nullable()->index();
            $table->timestamp('created_at')->index();

            $table->index(['level', 'created_at']);
        });

        // Voor PostgreSQL: extra indexes
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX logs_context_gin ON ' . config('db-logger.table', 'db_logger') . ' USING gin (context)');
            DB::statement('CREATE INDEX logs_extra_gin ON ' . config('db-logger.table', 'db_logger') . ' USING gin (extra)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('db-logger.table', 'db_logger'));
    }
};