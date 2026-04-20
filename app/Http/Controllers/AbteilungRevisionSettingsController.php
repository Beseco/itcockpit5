<?php

namespace App\Http\Controllers;

use App\Models\AbteilungRevisionSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbteilungRevisionSettingsController extends Controller
{
    public function index()
    {
        $settings = AbteilungRevisionSettings::getSingleton();
        return view('abteilungen.revision_settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'new_app_email'  => ['required', 'email', 'max:255'],
            'enabled'        => ['nullable', 'boolean'],
            'interval_weeks' => ['required', 'integer', 'in:1,2,4'],
            'weekday'        => ['required', 'integer', 'between:1,5'],
            'hour'           => ['required', 'integer', 'between:7,19'],
        ]);

        $settings = AbteilungRevisionSettings::getSingleton();
        $settings->fill([
            'new_app_email'  => $request->new_app_email,
            'enabled'        => $request->boolean('enabled'),
            'interval_weeks' => (int) $request->interval_weeks,
            'weekday'        => (int) $request->weekday,
            'hour'           => (int) $request->hour,
        ]);
        $settings->save();

        return redirect()->route('abteilungen.revision-settings')
            ->with('success', 'Einstellungen gespeichert.');
    }
}
