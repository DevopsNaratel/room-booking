<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        // Ensure ApiLogging runs first (creates trace id & context), then EnforceApiAccess
        $middleware->append(\App\Http\Middleware\ApiLoggingMiddleware::class);
        $middleware->append(\App\Http\Middleware\EnforceApiAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e): void {
            Log::error('System Exception', [
                'event'       => 'system.error',
                'message'     => $e->getMessage(),
                'exception'   => get_class($e),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
                'trace'       => substr($e->getTraceAsString(), 0, 1000), // Ambil sedikit stack trace
            ]);
        });
    })->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('bookings:mark-past-completed')->dailyAt('03:00');
    })->create();
