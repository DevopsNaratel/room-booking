<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnforceApiAccess
{
    /**
     * Handle an incoming request.
     * Reject non-API requests with JSON response.
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow health check and API routes
        if ($request->is('api/*') || $request->is('up')) {
            return $next($request);
        }

        // Allow if client expects JSON (Accept: application/json)
        if ($request->expectsJson() || $request->wantsJson()) {
            return $next($request);
        }

        // Otherwise reject with JSON message (not acceptable)
        $traceId = $request->header('X-Trace-Id') ?? null;
        Log::warning('Blocked non-API access', [
            'event' => 'access.blocked',
            'path' => $request->path(),
            'ip' => $request->ip(),
            'trace_id' => $traceId,
        ]);

        $payload = [
            'message' => 'This application is API-only. Please use the API at /api/v1',
        ];
        if ($traceId) {
            $payload['trace_id'] = $traceId;
        }

        return response()->json($payload, 406);
    }
}
