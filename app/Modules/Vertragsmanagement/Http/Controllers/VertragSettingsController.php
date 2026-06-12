<?php

namespace App\Modules\Vertragsmanagement\Http\Controllers;

use App\Modules\Vertragsmanagement\Models\VertragSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VertragSettingsController extends Controller
{
    public function index()
    {
        $settings = VertragSettings::getSingleton();

        return view('vertragsmanagement::settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'fallback_email' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = VertragSettings::getSingleton();
        $settings->fill([
            'fallback_email' => $request->input('fallback_email') ?: null,
        ])->save();

        return redirect()->route('vertragsmanagement.settings')
            ->with('success', 'Einstellungen gespeichert.');
    }
}
