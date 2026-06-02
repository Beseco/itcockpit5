<?php

namespace App\Modules\Baramundi\Http\Controllers;

use App\Modules\Baramundi\Models\BaraEvent;
use App\Modules\Baramundi\Models\WatchedPackage;
use App\Modules\Baramundi\Services\SmbScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class BaraPackageController extends Controller
{
    public function __construct(private readonly SmbScannerService $scanner) {}

    public function index(Request $request)
    {
        $search       = $request->input('search', '');
        $filterStatus = $request->input('status', '');

        $query = WatchedPackage::orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('server_name', 'like', "%{$search}%")
                  ->orWhere('share_path', 'like', "%{$search}%");
            });
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        $packages = $query->get();

        $statusOptions = WatchedPackage::STATUS_LABELS;

        return view('baramundi::index', compact('packages', 'search', 'filterStatus', 'statusOptions'));
    }

    public function create()
    {
        return view('baramundi::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePackage($request);

        WatchedPackage::create($data);

        return redirect()->route('baramundi.index')
            ->with('success', 'Paket "' . $data['name'] . '" wurde angelegt.');
    }

    public function edit(WatchedPackage $pkg)
    {
        return view('baramundi::edit', compact('pkg'));
    }

    public function update(Request $request, WatchedPackage $pkg): RedirectResponse
    {
        $data = $this->validatePackage($request);

        $pkg->update($data);

        BaraEvent::create([
            'package_id' => $pkg->id,
            'event_type' => 'config_changed',
            'version'    => null,
            'message'    => "Konfiguration geändert von " . (auth()->user()?->name ?? 'System'),
        ]);

        return redirect()->route('baramundi.packages.show', $pkg)
            ->with('success', 'Paket aktualisiert.');
    }

    public function destroy(WatchedPackage $pkg): RedirectResponse
    {
        $name = $pkg->name;
        $pkg->delete();

        return redirect()->route('baramundi.index')
            ->with('success', 'Paket "' . $name . '" wurde gelöscht.');
    }

    public function show(WatchedPackage $pkg)
    {
        $events = $pkg->events()
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('baramundi::show', compact('pkg', 'events'));
    }

    public function scan(WatchedPackage $pkg): JsonResponse
    {
        try {
            Artisan::call('bara:scan', [
                '--package'     => $pkg->id,
                '--no-download' => true,
            ]);

            $pkg->refresh();

            return response()->json([
                'success'            => true,
                'status'             => $pkg->status,
                'status_label'       => $pkg->getStatusLabel(),
                'status_color'       => $pkg->getStatusColor(),
                'last_known_version' => $pkg->last_known_version ?? '—',
                'last_scan'          => $pkg->last_scan?->format('d.m.Y H:i') ?? '—',
                'message'            => 'Scan abgeschlossen.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Scan: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'server_name'      => ['required', 'string', 'max:255'],
            'share_path'       => ['required', 'string', 'max:1000'],
            'enabled'          => ['nullable', 'boolean'],
            'email_enabled'    => ['nullable', 'boolean'],
            'download_type'    => ['required', 'in:none,http,powershell,batch'],
            'download_command' => ['nullable', 'string', 'max:2000'],
            'download_url'     => ['nullable', 'url', 'max:1000'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]) + [
            'enabled'       => false,
            'email_enabled' => false,
        ];
    }
}
