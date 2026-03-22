<?php

namespace App\Http\Middleware;

use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     * 
     * Checks if the module is active. Superadministrators bypass this check.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $moduleName  The technical name of the module (e.g., "announcements", "hh")
     */
    public function handle(Request $request, Closure $next, string $moduleName): Response
    {
        // Superadministrator bypasses all module checks
        if ($request->user() && $request->user()->hasRole('Superadministrator')) {
            return $next($request);
        }

        // Find the module by name
        $module = Module::where('name', $moduleName)->first();

        // If module doesn't exist, deny access
        if (!$module) {
            abort(404, 'Modul nicht gefunden.');
        }

        // If module is not active, deny access for non-superadmins
        if (!$module->isActive()) {
            abort(403, 'Dieses Modul ist derzeit nicht verfügbar.');
        }

        return $next($request);
    }
}
