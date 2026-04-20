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
            'new_app_email' => ['required', 'email', 'max:255'],
        ]);

        $settings = AbteilungRevisionSettings::getSingleton();
        $settings->fill(['new_app_email' => $request->new_app_email]);
        $settings->save();

        return redirect()->route('abteilungen.revision-settings')
            ->with('success', 'Einstellungen gespeichert.');
    }
}
