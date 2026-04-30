<?php

namespace App\Modules\Server\Http\Controllers;

use App\Modules\Server\Models\Server;
use App\Services\AuditLogger;
use App\Services\CheckMkService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CheckMkCompareController extends Controller
{
    public function __construct(
        private CheckMkService $svc,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        if (! $this->svc->isConfigured()) {
            return view('server::checkmk_compare', [
                'error'         => 'CheckMK ist nicht konfiguriert. Bitte Zugangsdaten unter Einstellungen hinterlegen.',
                'folders'       => [],
                'direction'     => null,
                'selectedFolders' => [],
                'onlyInCheckMk' => collect(),
                'notMonitored'  => collect(),
                'ran'           => false,
            ]);
        }

        $folders         = $this->svc->getFolders();
        $direction       = $request->input('direction'); // 'checkmk_to_cockpit' | 'cockpit_to_checkmk'
        $selectedFolders = $request->input('folders', []);

        // No direction chosen yet → show filter form only
        if (! in_array($direction, ['checkmk_to_cockpit', 'cockpit_to_checkmk'])) {
            return view('server::checkmk_compare', [
                'error'           => null,
                'folders'         => $folders,
                'direction'       => null,
                'selectedFolders' => [],
                'onlyInCheckMk'   => collect(),
                'notMonitored'    => collect(),
                'ran'             => false,
            ]);
        }

        // Fetch CheckMK hosts (folder-filtered only for checkmk→cockpit direction)
        $folderFilter = ($direction === 'checkmk_to_cockpit') ? $selectedFolders : [];
        $allHosts     = collect($this->svc->getAllHosts($folderFilter));

        $servers = Server::select('id', 'name', 'dns_hostname', 'checkmk_alias', 'status', 'type')
            ->orderBy('name')
            ->get();

        // Build IT-Cockpit lookup (full name + short name before first dot)
        $itCockpitMap = [];
        foreach ($servers as $s) {
            foreach ([$s->checkmk_alias, $s->dns_hostname, $s->name] as $identifier) {
                if ($identifier !== null && $identifier !== '') {
                    $lower = strtolower(trim($identifier));
                    $itCockpitMap[$lower] = $s->id;
                    $short = explode('.', $lower)[0];
                    if ($short !== $lower) {
                        $itCockpitMap[$short] = $s->id;
                    }
                }
            }
        }

        // Build CheckMK lookup (full + short)
        $checkmkNames = [];
        foreach ($allHosts->pluck('name') as $n) {
            $full  = strtolower(trim($n));
            $short = explode('.', $full)[0];
            $checkmkNames[$full]  = true;
            $checkmkNames[$short] = true;
        }

        // CheckMK hosts not in IT-Cockpit
        $onlyInCheckMk = $allHosts->filter(function ($h) use ($itCockpitMap) {
            $full  = strtolower(trim($h['name']));
            $short = explode('.', $full)[0];
            return ! isset($itCockpitMap[$full]) && ! isset($itCockpitMap[$short]);
        })->map(function ($h) {
            $tagValues = collect($h['tags'] ?? [])->values()->map(fn($v) => strtolower((string) $v));
            $suggestedType = 'vm';
            if ($tagValues->contains(fn($v) => str_contains($v, 'firewall'))) {
                $suggestedType = 'firewall';
            } elseif ($tagValues->contains(fn($v) => str_contains($v, 'usv') || str_contains($v, 'ups'))) {
                $suggestedType = 'usv';
            }
            $h['suggested_type'] = $suggestedType;
            return $h;
        })->values();

        // IT-Cockpit servers not monitored in CheckMK
        $notMonitored = $servers->filter(function ($s) use ($checkmkNames) {
            foreach ([$s->checkmk_alias, $s->dns_hostname, $s->name] as $identifier) {
                if ($identifier !== null && $identifier !== '') {
                    $lower = strtolower(trim($identifier));
                    $short = explode('.', $lower)[0];
                    if (isset($checkmkNames[$lower]) || isset($checkmkNames[$short])) {
                        return false;
                    }
                }
            }
            return true;
        });

        return view('server::checkmk_compare', [
            'error'           => null,
            'folders'         => $folders,
            'direction'       => $direction,
            'selectedFolders' => $selectedFolders,
            'onlyInCheckMk'   => $onlyInCheckMk,
            'notMonitored'    => $notMonitored,
            'ran'             => true,
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'hosts'              => ['required', 'array', 'min:1'],
            'hosts.*.name'       => ['required', 'string'],
            'hosts.*.type'       => ['required', 'in:vm,bare_metal,firewall,usv,sonstiges'],
            'hosts.*.address'    => ['nullable', 'string'],
            'hosts.*.alias'      => ['nullable', 'string'],
        ]);

        $imported = 0;
        $skipped  = 0;

        foreach ($validated['hosts'] as $host) {
            $cmkName = trim($host['name']);

            $exists = Server::where('checkmk_alias', $cmkName)
                ->orWhere('dns_hostname', $cmkName)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($cmkName)])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Server::create([
                'name'          => $cmkName,
                'checkmk_alias' => $cmkName,
                'dns_hostname'  => $cmkName,
                'ip_address'    => $host['address'] ?? null,
                'type'          => $host['type'],
                'status'        => 'produktiv',
            ]);
            $imported++;
        }

        $this->auditLogger->logModuleAction('Server', 'CheckMK-Import', [
            'imported' => $imported,
            'skipped'  => $skipped,
        ]);

        $msg = "{$imported} Gerät(e) importiert" . ($skipped > 0 ? ", {$skipped} bereits vorhanden und übersprungen" : '') . '.';

        return redirect()->route('server.checkmk.compare', ['direction' => 'checkmk_to_cockpit'])
            ->with('success', $msg);
    }
}
