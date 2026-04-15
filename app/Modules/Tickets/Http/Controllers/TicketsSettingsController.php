<?php

namespace App\Modules\Tickets\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tickets\Models\TicketsSettings;
use App\Modules\Tickets\Services\ZammadService;
use Illuminate\Http\Request;

class TicketsSettingsController extends Controller
{
    public function index()
    {
        $settings = TicketsSettings::getSingleton();

        return view('tickets::settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'url'       => ['required', 'url', 'max:500'],
            'api_token' => ['nullable', 'string', 'max:500'],
            'enabled'   => ['nullable'],
        ]);

        $settings = TicketsSettings::getSingleton();
        $settings->url     = $request->input('url');
        $settings->enabled = $request->boolean('enabled');

        // Token nur aktualisieren, wenn ein neuer Wert eingegeben wurde
        if ($request->filled('api_token')) {
            $settings->api_token = $request->input('api_token');
        }

        $settings->save();

        return redirect()->route('tickets.settings')
            ->with('success', 'Einstellungen wurden gespeichert.');
    }

    public function testConnection()
    {
        $service = new ZammadService();
        $result = $service->testConnection();

        return response()->json($result);
    }
}
