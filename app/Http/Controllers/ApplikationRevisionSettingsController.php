<?php

namespace App\Http\Controllers;

use App\Models\ApplikationRevisionSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplikationRevisionSettingsController extends Controller
{
    public function index()
    {
        $settings = ApplikationRevisionSettings::getSingleton();
        return view('applikationen.revision_settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled'        => ['nullable', 'boolean'],
            'interval_weeks' => ['required', 'integer', 'in:1,2,4'],
            'weekday'        => ['required', 'integer', 'between:1,5'],
            'hour'           => ['required', 'integer', 'between:7,19'],
        ]);

        $settings = ApplikationRevisionSettings::getSingleton();
        $settings->fill([
            'enabled'        => $request->boolean('enabled'),
            'interval_weeks' => (int) $request->interval_weeks,
            'weekday'        => (int) $request->weekday,
            'hour'           => (int) $request->hour,
        ]);
        $settings->save();

        return redirect()->route('applikationen.revision-settings')
            ->with('success', 'Einstellungen gespeichert.');
    }
}
