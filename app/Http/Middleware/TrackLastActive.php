<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Nur aktualisieren wenn der letzte Eintrag älter als 5 Minuten ist
            if (is_null($user->last_active_at) || $user->last_active_at->lt(now()->subMinutes(5))) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['last_active_at' => now()]);
                $user->last_active_at = now();
            }
        }

        return $next($request);
    }
}
