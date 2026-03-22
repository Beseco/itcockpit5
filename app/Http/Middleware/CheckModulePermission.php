<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, string $permission): Response
    {
        // Note: Authentication is handled by the 'auth' middleware which runs before this middleware
        // So we can assume the user is authenticated at this point
        
        // Super-admin bypasses all permission checks
        if ($request->user()->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required module permission
        if (!$request->user()->hasModulePermission($module, $permission)) {
            // Log unauthorized access attempt (insufficient permissions)
            $auditLogger = app(\App\Services\AuditLogger::class);
            $auditLogger->log('Security', 'Unauthorized access attempt - insufficient permissions', [
                'module' => $module,
                'permission' => $permission,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
            ]);
            
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}
