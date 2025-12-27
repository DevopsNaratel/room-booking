<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiLoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // 1. Generate atau ambil Trace ID
        $traceId = $request->header('X-Trace-Id') ?? (string) Str::uuid();

        // 2. Share Context: Semua Log::info/error di aplikasi akan otomatis punya data ini
        Log::shareContext([
            'trace_id'   => $traceId,
            'user_id'    => $request->user()?->id ?? 'guest',
            'user_email' => $request->user()?->email ?? 'guest',
            'client_ip'  => $request->ip(),
            'app_name'   => 'room-booking-api',
        ]);

        $response = $next($request);

        // 3. Log Otomatis di akhir request (Setiap Hit)
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Incoming Request', [
            'event'       => 'http.request',
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            // 'payload'  => $request->except(['password', 'token']), // Opsional jika butuh payload
        ]);

        // Kirim trace_id ke header response agar user bisa lapor ID ini jika error
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }
}