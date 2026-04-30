<?php

namespace App\Modules\Server\Http\Controllers;

use App\Models\Abteilung;
use App\Models\Gruppe;
use App\Models\User;
use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
use App\Services\AuditLogger;
use App\Services\CheckMkService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class CheckMkCompareController extends Controller
{
    public function __construct(
        private CheckMkService $svc,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $deviceTypes = ServerOption::category('geraet_typ')->pluck('label');

        if (! $this->svc->isConfigured()) {
            return view('server::checkmk_compare', [
                'error'           => 'CheckMK ist nicht konfiguriert. Bitte Zugangsdaten unter Einstellungen hinterlegen.',
                'folders'         => [],
                'foldersError'    => null,
                'direction'       => null,
                'selectedFolders' => [],
                'onlyInCheckMk'   => collect(),
                'notMonitored'    => collect(),
                'ran'             => false,
                'deviceTypes'     => $deviceTypes,
                'abteilungen'     => collect(),
                'adminUsers'      => collect(),
                'gruppen'         => collect(),
            ]);
        }

        $foldersError = null;
        try {
            $folders = $this->svc->getFolders();
        } catch (\Exception $e) {
            $folders = [];
            $msg = $e->getMessage();
            if (str_contains($msg, '401') || str_contains($msg, 'Unauthorized') || str_contains($msg, 'no permissions')) {
                $foldersError = 'Ordner-Filter nicht verfügbar: Der CheckMK-API-Nutzer hat keine Leseberechtigung auf das Hauptverzeichnis. Alle erreichbaren Hosts werden verglichen.';
            } else {
                $foldersError = 'Ordner konnten nicht geladen werden: ' . $msg;
            }
            Log::warning('CheckMK getFolders: ' . $msg);
        }

        $direction       = $request->input('direction');
        $selectedFolders = $request->input('folders', []);

        // Lookup data for import form
        $abteilungen = Abteilung::orderBy('kurzzeichen')->orderBy('name')->get(['id', 'name', 'kurzzeichen']);
        $adminUsers  = User::orderBy('name')->get(['id', 'name']);
        $gruppen     = Gruppe::orderBy('name')->get(['id', 'name']);

        $base = [
            'error'           => null,
            'foldersError'    => $foldersError,
            'folders'         => $folders,
            'direction'       => $direction,
            'selectedFolders' => $selectedFolders,
            'deviceTypes'     => $deviceTypes,
            'abteilungen'     => $abteilungen,
            'adminUsers'      => $adminUsers,
            'gruppen'         => $gruppen,
        ];

        if (! in_array($direction, ['checkmk_to_cockpit', 'cockpit_to_checkmk'])) {
            return view('server::checkmk_compare', array_merge($base, [
                'onlyInCheckMk' => collect(),
                'notMonitored'  => collect(),
                'ran'           => false,
            ]));
        }

        $folderFilter = ($direction === 'checkmk_to_cockpit') ? $selectedFolders : [];
        $allHosts     = collect($this->svc->getAllHosts($folderFilter))
            ->filter(fn($h) => $this->isProductiveHost($h))
            ->values();

        $servers = Server::select('id', 'name', 'dns_hostname', 'checkmk_alias', 'ip_address', 'status', 'type')
            ->orderBy('name')
            ->get();

        // IT-Cockpit lookup: hostname variants + IP address
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
            // IP address match
            if (!empty($s->ip_address)) {
                $itCockpitMap[trim($s->ip_address)] = $s->id;
            }
        }

        // CheckMK lookup: hostname variants + IP address
        $checkmkLookup = [];
        foreach ($allHosts as $h) {
            $full  = strtolower(trim($h['name']));
            $short = explode('.', $full)[0];
            $checkmkLookup[$full]  = true;
            $checkmkLookup[$short] = true;
            if (!empty($h['address'])) {
                $checkmkLookup[trim($h['address'])] = true;
            }
        }

        // CheckMK hosts not in IT-Cockpit
        $onlyInCheckMk = $allHosts->filter(function ($h) use ($itCockpitMap) {
            $full  = strtolower(trim($h['name']));
            $short = explode('.', $full)[0];
            $ip    = trim($h['address'] ?? '');
            return ! isset($itCockpitMap[$full])
                && ! isset($itCockpitMap[$short])
                && ($ip === '' || ! isset($itCockpitMap[$ip]));
        })->map(function ($h) use ($deviceTypes) {
            $tagValues = collect($h['tags'] ?? [])->values()->map(fn($v) => strtolower((string) $v));
            $suggested = $deviceTypes->first() ?? '';
            foreach ($tagValues as $tag) {
                if (str_contains($tag, 'firewall')) { $suggested = $deviceTypes->firstWhere(fn($t) => stripos($t, 'firewall') !== false) ?? $suggested; break; }
                if (str_contains($tag, 'usv') || str_contains($tag, 'ups')) { $suggested = $deviceTypes->firstWhere(fn($t) => stripos($t, 'usv') !== false) ?? $suggested; break; }
            }
            $h['suggested_type'] = $suggested;
            return $h;
        })->values();

        // IT-Cockpit servers not in CheckMK
        $notMonitored = $servers->filter(function ($s) use ($checkmkLookup) {
            foreach ([$s->checkmk_alias, $s->dns_hostname, $s->name] as $identifier) {
                if ($identifier !== null && $identifier !== '') {
                    $lower = strtolower(trim($identifier));
                    $short = explode('.', $lower)[0];
                    if (isset($checkmkLookup[$lower]) || isset($checkmkLookup[$short])) return false;
                }
            }
            if (!empty($s->ip_address) && isset($checkmkLookup[trim($s->ip_address)])) return false;
            return true;
        });

        return view('server::checkmk_compare', array_merge($base, [
            'onlyInCheckMk' => $onlyInCheckMk,
            'notMonitored'  => $notMonitored,
            'ran'           => true,
        ]));
    }

    public function import(Request $request)
    {
        $deviceTypeLabels = ServerOption::category('geraet_typ')->pluck('label')->toArray();

        $validated = $request->validate([
            'hosts'                  => ['required', 'array', 'min:1'],
            'hosts.*.name'           => ['required', 'string'],
            'hosts.*.type'           => ['required', 'string', 'in:' . implode(',', $deviceTypeLabels)],
            'hosts.*.address'        => ['nullable', 'string'],
            'hosts.*.alias'          => ['nullable', 'string'],
            'hosts.*.abteilung_id'   => ['nullable', 'integer', 'exists:abteilungen,id'],
            'hosts.*.admin_user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'hosts.*.gruppe_id'      => ['nullable', 'integer', 'exists:gruppen,id'],
        ]);

        $imported = 0;
        $skipped  = 0;

        foreach ($validated['hosts'] as $host) {
            $cmkName = trim($host['name']);
            $ip      = trim($host['address'] ?? '');

            $query = Server::where('checkmk_alias', $cmkName)
                ->orWhere('dns_hostname', $cmkName)
                ->orWhereRaw('LOWER(name) = ?', [strtolower($cmkName)]);
            if ($ip !== '') {
                $query->orWhere('ip_address', $ip);
            }

            if ($query->exists()) {
                $skipped++;
                continue;
            }

            Server::create(array_filter([
                'name'          => $cmkName,
                'checkmk_alias' => $cmkName,
                'dns_hostname'  => $cmkName,
                'ip_address'    => $ip ?: null,
                'type'          => $host['type'],
                'status'        => 'produktiv',
                'abteilung_id'  => $host['abteilung_id'] ?? null,
                'admin_user_id' => $host['admin_user_id'] ?? null,
                'gruppe_id'     => $host['gruppe_id'] ?? null,
            ], fn($v) => $v !== null));

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

    /**
     * Prüft ob ein CheckMK-Host die Criticality "Productive system" (Tag-ID "prod") hat.
     * CheckMK liefert Tags als flaches Array: ['criticality' => 'prod', ...] oder
     * mit Prefix: ['tag_criticality' => 'prod', ...].
     */
    private function isProductiveHost(array $host): bool
    {
        $tags = $host['tags'] ?? [];
        // Criticality-Tag unter verschiedenen möglichen Schlüsseln prüfen
        $criticality = $tags['criticality'] ?? $tags['tag_criticality'] ?? null;
        if ($criticality !== null) {
            return $criticality === 'prod';
        }
        // Falls kein Criticality-Tag gesetzt: Host einschließen (kein Ausschluss)
        return true;
    }
}
