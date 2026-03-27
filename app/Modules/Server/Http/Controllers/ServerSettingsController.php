<?php

namespace App\Modules\Server\Http\Controllers;

use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
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

        return view('server::settings.index', compact('options', 'lastSync'));
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
