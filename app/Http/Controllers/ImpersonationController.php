<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function impersonate(User $user): RedirectResponse
    {
        $actor = auth()->user();

        // Nur Superadmin oder User mit base.users.edit dürfen impersonieren
        if (!$actor->isSuperAdmin() && !$actor->can('base.users.edit')) {
            abort(403);
        }

        // Eigenen Account kann man nicht impersonieren
        if ($actor->id === $user->id) {
            return back()->with('error', 'Sie können sich nicht selbst impersonieren.');
        }

        // Superadministrator kann nicht impersoniert werden (außer von einem anderen Superadmin)
        if ($user->isSuperAdmin() && !$actor->isSuperAdmin()) {
            return back()->with('error', 'Superadministratoren können nicht impersoniert werden.');
        }

        // Bereits eine laufende Impersonation → nicht verschachteln
        if (Session::has('impersonating_original_id')) {
            return back()->with('error', 'Bitte beenden Sie zuerst die aktuelle Impersonation.');
        }

        // Original-ID in Session speichern, dann einloggen
        Session::put('impersonating_original_id', $actor->id);
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('info', 'Sie sind jetzt als "' . $user->name . '" angemeldet.');
    }

    public function stop(): RedirectResponse
    {
        $originalId = Session::get('impersonating_original_id');

        if (!$originalId) {
            return redirect()->route('dashboard');
        }

        $originalUser = User::find($originalId);

        Session::forget('impersonating_original_id');

        if (!$originalUser) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Original-Benutzer nicht gefunden.');
        }

        Auth::login($originalUser);

        return redirect()->route('users.index')
            ->with('success', 'Impersonation beendet. Sie sind wieder als sich selbst angemeldet.');
    }
}
