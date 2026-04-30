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

    public function index()
    {
        if (! $this->svc->isConfigured()) {
            return view('server::checkmk_compare', [
                'error'         => 'CheckMK ist nicht konfiguriert. Bitte Zugangsdaten unter Einstellungen hinterlegen.',
                'onlyInCheckMk' => collect(),
                'notMonitored'  => collect(),
            ]);
        }

        $allHosts = collect($this->svc->getAllHosts());

        $servers = Server::select('id', 'name', 'dns_hostname', 'checkmk_alias', 'status', 'type')
            ->orderBy('name')
            ->get();

        // Build lookup map: all known IT-Cockpit identifiers (lowercase)
        // Also adds short hostname (before first dot) so "nextcloud-01.lra.lan" matches "nextcloud-01"
        $itCockpitMap = [];
        foreach ($servers as $s) {
            foreach ([$s->checkmk_alias, $s->dns_hostname, $s->name] as $identifier) {
                if ($identifier !== null && $identifier !== '') {
                    $lower = strtolower(trim($identifier));
                    $itCockpitMap[$lower] = $s->id;
                    // Add short name variant
                    $short = explode('.', $lower)[0];
                    if ($short !== $lower) {
                        $itCockpitMap[$short] = $s->id;
                    }
                }
            }
        }

        // Hosts in CheckMK but not in IT-Cockpit
        // Match by full name AND short name (strip domain suffix)
        $onlyInCheckMk = $allHosts->filter(function ($h) use ($itCockpitMap) {
            $cmkFull  = strtolower(trim($h['name']));
            $cmkShort = explode('.', $cmkFull)[0];
            return ! isset($itCockpitMap[$cmkFull]) && ! isset($itCockpitMap[$cmkShort]);
        })->map(function ($h) {
            $tags = $h['tags'] ?? [];
            $tagValues = collect($tags)->values()->map(fn($v) => strtolower((string) $v));
            $suggestedType = 'vm';
            if ($tagValues->contains(fn($v) => str_contains($v, 'firewall'))) {
                $suggestedType = 'firewall';
            } elseif ($tagValues->contains(fn($v) => str_contains($v, 'usv') || str_contains($v, 'ups'))) {
                $suggestedType = 'usv';
            }
            $h['suggested_type'] = $suggestedType;
            return $h;
        })->values();

        // Build CheckMK name set: full names AND short names (before first dot)
        $checkmkNames = [];
        foreach ($allHosts->pluck('name') as $n) {
            $full  = strtolower(trim($n));
            $short = explode('.', $full)[0];
            $checkmkNames[$full]  = true;
            $checkmkNames[$short] = true;
        }

        // IT-Cockpit servers with no match in CheckMK
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

        return view('server::checkmk_compare', compact('onlyInCheckMk', 'notMonitored'));
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

            // Skip if already exists
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

        return redirect()->route('server.checkmk.compare')->with('success', $msg);
    }
}
