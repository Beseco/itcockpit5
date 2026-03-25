<?php

namespace App\Modules\AdUsers\Http\Controllers;

use App\Modules\AdUsers\Models\AdUserSettings;
use App\Modules\AdUsers\Services\AdUserSyncService;
use App\Modules\AdUsers\Services\LdapConnectionService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdUserSettingsController extends Controller
{
    public function index()
    {
        $settings = AdUserSettings::getSingleton();
        return view('adusers::settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'server'              => ['required', 'string', 'max:255'],
            'port'                => ['required', 'integer', 'min:1', 'max:65535'],
            'base_dn'             => ['required', 'string', 'max:500'],
            'bind_dn'             => ['nullable', 'string', 'max:500'],
            'bind_password'       => ['nullable', 'string', 'max:500'],
            'anonymous_bind'      => ['boolean'],
            'use_ssl'             => ['boolean'],
            'sync_interval_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'max_inactive_days'   => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $settings = AdUserSettings::getSingleton();
        $data = $request->only([
            'server', 'port', 'base_dn', 'bind_dn',
            'anonymous_bind', 'use_ssl', 'sync_interval_hours', 'max_inactive_days',
        ]);
        $data['anonymous_bind'] = $request->boolean('anonymous_bind');
        $data['use_ssl']        = $request->boolean('use_ssl');

        // Passwort nur aktualisieren wenn eingegeben
        if ($request->filled('bind_password')) {
            $data['bind_password'] = $request->bind_password;
        }

        $settings->fill($data);
        $settings->save();

        return redirect()->route('adusers.settings')->with('success', 'Einstellungen gespeichert.');
    }

    public function testConnection(): JsonResponse
    {
        $result = (new LdapConnectionService())->testConnection();
        return response()->json($result);
    }

    public function testQuery(): JsonResponse
    {
        $result = (new LdapConnectionService())->testQuery();
        return response()->json($result);
    }

    public function runSync(Request $request)
    {
        try {
            $syncService = app(AdUserSyncService::class);
            $result = $syncService->sync();

            return redirect()->route('adusers.index')
                ->with('success', "Synchronisation abgeschlossen: {$result['updated']} aktualisiert, {$result['deactivated']} als nicht vorhanden markiert.");
        } catch (\Exception $e) {
            return redirect()->route('adusers.settings')
                ->with('error', 'Synchronisation fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
