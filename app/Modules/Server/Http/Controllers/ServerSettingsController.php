<?php

namespace App\Modules\Server\Http\Controllers;

use App\Modules\Server\Models\CheckMkSettings;
use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
use App\Modules\Server\Models\ServerSyncOu;
use App\Modules\Server\Models\VsphereSettings;
use App\Modules\Server\Services\ServerSyncService;
use App\Modules\Server\Services\VsphereService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ServerSettingsController extends Controller
{
    public function index()
    {
        $options          = collect(ServerOption::CATEGORIES)->mapWithKeys(
            fn ($cat) => [$cat => ServerOption::category($cat)->get()]
        );
        $lastSync         = Server::whereNotNull('last_sync_at')->max('last_sync_at');
        $syncOus          = ServerSyncOu::orderBy('sort_order')->get();
        $checkMkSettings  = CheckMkSettings::getSingleton();
        $vsphereSettings  = VsphereSettings::getSingleton();

        return view('server::settings.index', compact(
            'options', 'lastSync', 'syncOus', 'checkMkSettings', 'vsphereSettings'
        ));
    }

    public function storeOption(Request $request)
    {
        $request->validate([
            'category' => ['required', 'in:' . implode(',', ServerOption::CATEGORIES)],
            'label'    => ['required', 'string', 'max:100'],
        ]);

        ServerOption::firstOrCreate(
            ['category' => $request->category, 'label' => $request->label],
            ['sort_order' => ServerOption::where('category', $request->category)->max('sort_order') + 1]
        );

        return redirect()->route('server.settings')->with('success', 'Option gespeichert.');
    }

    public function destroyOption(ServerOption $option)
    {
        $option->delete();
        return redirect()->route('server.settings')->with('success', 'Option gelöscht.');
    }

    public function storeOu(Request $request)
    {
        $request->validate([
            'distinguished_name' => ['required', 'string', 'max:500'],
            'label'              => ['nullable', 'string', 'max:255'],
        ]);

        ServerSyncOu::create([
            'distinguished_name' => trim($request->distinguished_name),
            'label'              => $request->label ? trim($request->label) : null,
            'enabled'            => true,
            'sort_order'         => ServerSyncOu::max('sort_order') + 1,
        ]);

        return redirect()->route('server.settings')->with('success', 'OU hinzugefügt.');
    }

    public function destroyOu(ServerSyncOu $ou)
    {
        $ou->delete();
        return redirect()->route('server.settings')->with('success', 'OU gelöscht.');
    }

    public function toggleOu(ServerSyncOu $ou)
    {
        $ou->update(['enabled' => !$ou->enabled]);
        return redirect()->route('server.settings')
            ->with('success', $ou->enabled ? 'OU aktiviert.' : 'OU deaktiviert.');
    }

    public function runSync()
    {
        try {
            $result = app(ServerSyncService::class)->sync();
            return redirect()->route('server.index')
                ->with('success', "Sync abgeschlossen: {$result['synced']} synchronisiert, {$result['marked_unsynced']} als nicht synchronisiert markiert.");
        } catch (\Exception $e) {
            return redirect()->route('server.settings')
                ->with('error', 'Synchronisation fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function storeVsphereSettings(Request $request)
    {
        $request->validate([
            'vcenter_url' => ['required', 'string', 'max:500'],
            'username'    => ['required', 'string', 'max:255'],
            'password'    => ['nullable', 'string'],
            'verify_ssl'  => ['nullable', 'boolean'],
        ]);

        $settings = VsphereSettings::getSingleton();
        $settings->fill([
            'enabled'     => $request->boolean('enabled'),
            'vcenter_url' => rtrim(trim($request->vcenter_url), '/'),
            'username'    => trim($request->username),
            'verify_ssl'  => $request->boolean('verify_ssl'),
        ]);

        // Only overwrite password if a new one was entered
        if (filled($request->password)) {
            $settings->password = $request->password;
        }

        $settings->save();

        return redirect()->route('server.settings')->with('success', 'vSphere-Einstellungen gespeichert.');
    }

    public function testVsphereConnection(Request $request): JsonResponse
    {
        $request->validate([
            'vcenter_url' => ['required', 'string'],
            'username'    => ['required', 'string'],
            'password'    => ['nullable', 'string'],
            'verify_ssl'  => ['nullable', 'boolean'],
        ]);

        $saved = VsphereSettings::getSingleton();

        $temp              = new VsphereSettings();
        $temp->enabled     = true;
        $temp->vcenter_url = rtrim(trim($request->vcenter_url), '/');
        $temp->username    = trim($request->username);
        $temp->password    = filled($request->password) ? $request->password : $saved->password;
        $temp->verify_ssl  = $request->boolean('verify_ssl');

        $service = new VsphereService($temp);
        return response()->json($service->testConnection());
    }

    public function runVsphereSync()
    {
        $service = VsphereService::make();

        if (!$service->isConfigured()) {
            return redirect()->route('server.settings')
                ->with('error', 'vSphere ist nicht konfiguriert oder deaktiviert.');
        }

        try {
            $result = $service->sync();
            return redirect()->route('server.index')
                ->with('success', "vSphere-Sync abgeschlossen: {$result['updated']} aktualisiert, {$result['created']} neu angelegt, {$result['skipped']} übersprungen.");
        } catch (\Exception $e) {
            return redirect()->route('server.settings')
                ->with('error', 'vSphere-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
