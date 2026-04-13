<?php

namespace App\Modules\Server\Http\Controllers;

use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
use App\Modules\Server\Models\ServerSyncOu;
use App\Modules\Server\Services\ServerSyncService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ServerSettingsController extends Controller
{
    public function index()
    {
        $options  = collect(ServerOption::CATEGORIES)->mapWithKeys(
            fn ($cat) => [$cat => ServerOption::category($cat)->get()]
        );
        $lastSync = Server::whereNotNull('last_sync_at')->max('last_sync_at');
        $syncOus  = ServerSyncOu::orderBy('sort_order')->get();

        return view('server::settings.index', compact('options', 'lastSync', 'syncOus'));
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
}
