<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\ApiLoggingMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            Log::error($e->getMessage(), [
                'exception' => $e,
                'error_code' => 'SYSTEM_ERROR',
            ]);
        });
    })->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('bookings:mark-past-completed')->dailyAt('03:00');
    })->create();
