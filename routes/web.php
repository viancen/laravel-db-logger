<?php

use Illuminate\Support\Facades\Route;
use Viancen\LaravelDbLogger\Http\Controllers\LogsController;

Route::prefix(config('db-logger.route_prefix', 'logs'))
    ->middleware(config('db-logger.middleware', ['web', 'auth']))
    ->group(function () {
        Route::get('/', [LogsController::class, 'index'])->name('db-logger.index');
        Route::get('/data', [LogsController::class, 'data'])->name('db-logger.data');
    });