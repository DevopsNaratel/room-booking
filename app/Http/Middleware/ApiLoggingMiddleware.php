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

        // 1. Generate atau ambil Request ID
        $requestId = $request->header('X-Request-Id') ?? $request->header('X-Trace-Id') ?? (string) Str::uuid();

        // 2. Share Context: Semua Log::info/error di aplikasi akan otomatis punya data ini
        Log::shareContext([
            'requestId' => $requestId,
            'method' => $request->method(),
            'path' => $request->getPathInfo(),
            'user_id' => $request->user()?->id ?? 'guest',
        ]);

        $response = $next($request);

        // 3. Log Otomatis di akhir request (Setiap Hit)
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('Incoming request to ' . $request->getPathInfo(), [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'source' => $request->header('User-Agent'),
        ]);

        // Kirim requestId ke header response agar user bisa lapor ID ini jika error
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}