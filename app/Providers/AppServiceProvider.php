<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            \Illuminate\Support\Facades\Log::info('Executed query', [
                'query' => $query->sql,
                'duration_ms' => $query->time,
                'rows_affected' => count($query->bindings),
                'method' => 'INTERNAL',
            ]);
        });
    }
}
