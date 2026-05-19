<?php

namespace App\Modules\Wid\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wid\Models\WidAdvisory;
use App\Modules\Wid\Models\WidSettings;
use App\Modules\Wid\Services\WidService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class WidSettingsController extends Controller
{
    public function edit()
    {
        $settings = WidSettings::getInstance();
        return view('wid::settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'api_url'            => ['required', 'url', 'max:500'],
            'api_key'            => ['nullable', 'string', 'max:500'],
            'enabled'            => ['boolean'],
            'max_items'          => ['required', 'integer', 'min:1', 'max:200'],
            'min_classification' => ['required', 'in:keine,niedrig,mittel,hoch,kritisch'],
        ]);

        $settings = WidSettings::getInstance();

        // API-Key nur überschreiben wenn neu eingegeben
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $data['enabled'] = $request->boolean('enabled');
        $settings->fill($data);
        $settings->save();

        Cache::forget('wid_advisories_raw');

        return redirect()->route('wid.settings')->with('success', 'Einstellungen gespeichert.');
    }

    public function fetchNow()
    {
        Artisan::call('wid:fetch-advisories');
        $output = Artisan::output();

        return redirect()->route('wid.index')->with('success', 'WID-Abruf abgeschlossen: ' . trim($output));
    }
}
