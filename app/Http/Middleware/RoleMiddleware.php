<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if ($request->user()->role !== $role) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized role access attempt', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'required_role' => $role,
                'actual_role' => $request->user()->role,
            ]);
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
