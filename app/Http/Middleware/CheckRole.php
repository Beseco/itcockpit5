<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            abort(403, 'Unauthorized access.');
        }

        // Super-admin bypasses all role checks
        if ($request->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required role
        if ($request->user()->role !== $role) {
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}
