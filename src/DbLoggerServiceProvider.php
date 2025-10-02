<?php

namespace Viancen\LaravelDbLogger;

use Illuminate\Support\ServiceProvider;

class DbLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/db-logger.php', 'db-logger'
        );
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/db-logger.php' => config_path('db-logger.php'),
        ], 'db-logger-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'db-logger-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/db-logger'),
        ], 'db-logger-views');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/db-logger'),
        ], 'db-logger-assets');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'db-logger');

        // Load routes - gebruik config voor middleware
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\TestLogCommand::class,
            ]);
        }

        // Register custom log channel
        $this->app['log']->extend('database', function ($app, array $config) {
            return new \Viancen\LaravelDbLogger\Logging\CreateDatabaseLogger()($config);
        });
    }
}