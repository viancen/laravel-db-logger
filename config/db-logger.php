<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Logger Configuration
    |--------------------------------------------------------------------------
    */

    // Route prefix voor dashboard
    'route_prefix' => env('DB_LOGGER_ROUTE_PREFIX', 'logs'),

    // Middleware voor routes
    'middleware' => ['web', 'auth'],

    // Default filters
    'defaults' => [
        'per_page' => 50,
        'levels' => [3, 4, 5, 6, 7], // ERROR, WARNING, NOTICE, INFO, DEBUG
        'hours_back' => 24,
    ],

    // Table name
    'table' => 'logs',

    // Enable/disable logging
    'enabled' => env('DB_LOGGER_ENABLED', true),

    // Log level (Monolog Level)
    'level' => env('DB_LOGGER_LEVEL', 'debug'),
];