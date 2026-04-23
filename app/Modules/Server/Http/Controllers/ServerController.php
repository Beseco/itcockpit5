<?php

namespace App\Modules\Server\Http\Controllers;

use App\Models\Abteilung;
use App\Models\Applikation;
use App\Models\Gruppe;
use App\Models\User;
use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
use App\Modules\Server\Models\ServerSyncOu;
use App\Modules\Server\Models\VsphereSettings;
use App\Modules\Server\Services\ServerSyncService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $search          = $request->get('search', '');
        $filterStatus    = $request->get('filter_status', '');
        $filterAbt       = $request->get('filter_abteilung_id', '');
        $filterLdap      = $request->get('filter_ldap', '');
        $filterAdminId   = $request->get('filter_admin_id', '');
        $filterNoRevision= $request->boolean('filter_no_revision');

        $query = Server::with(['abteilung', 'adminUser', 'gruppe', 'osType', 'role', 'backupLevel', 'patchRing'])
            ->orderBy('name');

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dns_hostname', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (filled($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        if (filled($filterAbt)) {
            $query->where('abteilung_id', (int) $filterAbt);
        }

        if ($filterLdap === 'synced') {
            $query->where('ldap_synced', true);
        } elseif ($filterLdap === 'manual') {
            $query->where('ldap_synced', false);
        }

        if ($filterAdminId === '__mine__') {
            $query->where('admin_user_id', Auth::id());
        } elseif ($filterAdminId === '__none__') {
            $query->whereNull('admin_user_id');
        } elseif (filled($filterAdminId)) {
            $query->where('admin_user_id', (int) $filterAdminId);
        }

        if ($filterNoRevision) {
            $query->whereNull('revision_date');
        }

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $servers     = $query->paginate($perPage)->withQueryString();
        $abteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();
        $adminUsers  = User::where('is_active', true)->orderBy('name')->get();

        // Anzahl Server ohne Revisionsdatum für Aktionsbutton
        $countNoRevision  = Server::whereNull('revision_date')->count();
        $totalServers     = Server::count();
        $ldapEnabled      = ServerSyncOu::where('enabled', true)->exists();
        $vsphereEnabled   = VsphereSettings::getSingleton()->enabled;

        // Network-Modul: VLAN + Online-Status per IP-Abgleich (optional)
        $networkData = $this->loadNetworkData($servers->pluck('ip_address')->filter()->toArray());

        return response()
            ->view('server::index', compact(
                'servers', 'abteilungen', 'adminUsers', 'countNoRevision', 'totalServers', 'networkData',
                'search', 'filterStatus', 'filterAbt', 'filterLdap',
                'filterAdminId', 'filterNoRevision', 'perPage',
                'ldapEnabled', 'vsphereEnabled'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function show(Server $server)
    {
        $server->load(['abteilung', 'adminUser', 'gruppe', 'osType', 'role', 'backupLevel', 'patchRing', 'applikationen']);
        $networkEntry = $server->ip_address
            ? $this->loadNetworkData([$server->ip_address])[$server->ip_address] ?? null
            : null;
        return view('server::show', compact('server', 'networkEntry'));
    }

    public function create()
    {
        return view('server::create', array_merge($this->formData(), ['server' => null]));
    }

    public function store(Request $request)
    {
        $validated      = $this->validateServer($request);
        $applikationIds = $request->input('applikation_ids', []);
        unset($validated['applikation_ids']);

        $validated['revision_date'] = now()->addMonths(12);
        $server = Server::create($validated);
        $server->applikationen()->sync($applikationIds);

        $this->auditLogger->logModuleAction('Server', 'Server erstellt', [
            'id'   => $server->id,
            'name' => $server->name,
        ]);

        return redirect()->route('server.show', $server)
            ->with('success', 'Server erfolgreich angelegt.');
    }

    public function edit(Server $server)
    {
        return view('server::edit', array_merge($this->formData(), compact('server')));
    }

    public function update(Request $request, Server $server)
    {
        $validated      = $this->validateServer($request);
        $applikationIds = $request->input('applikation_ids', []);
        unset($validated['applikation_ids']);

        $server->update($validated);
        $server->applikationen()->sync($applikationIds);

        $this->auditLogger->logModuleAction('Server', 'Server aktualisiert', [
            'id'   => $server->id,
            'name' => $server->name,
        ]);

        return redirect()->route('server.show', $server)
            ->with('success', 'Server erfolgreich gespeichert.');
    }

    public function destroy(Server $server)
    {
        if ($server->ldap_synced && ServerSyncOu::where('enabled', true)->exists()) {
            return redirect()->route('server.index')
                ->with('error', '"' . $server->name . '" kann nicht geloescht werden: Server ist noch in LDAP synchronisiert.');
        }

        if ($server->vsphere_synced && VsphereSettings::getSingleton()->enabled) {
            return redirect()->route('server.index')
                ->with('error', '"' . $server->name . '" kann nicht geloescht werden: Server ist noch in vSphere vorhanden.');
        }

        $name = $server->name;
        $server->delete();

        $this->auditLogger->logModuleAction('Server', 'Server gelöscht', ['name' => $name]);

        return redirect()->route('server.index')
            ->with('success', 'Server "' . $name . '" wurde gelöscht.');
    }

    /**
     * Revision als durchgeführt markieren – setzt neues Datum in 12 Monaten.
     */
    public function markRevisionDone(Server $server)
    {
        $server->update(['revision_date' => now()->addMonths(12)]);

        $this->auditLogger->logModuleAction('Server', 'Revision durchgeführt', [
            'id'           => $server->id,
            'name'         => $server->name,
            'next_revision'=> $server->revision_date->format('d.m.Y'),
        ]);

        return redirect()->route('server.show', $server)
            ->with('success', 'Revision durchgeführt. Nächste Revision: ' . now()->addMonths(12)->format('d.m.Y'));
    }

    /**
     * Setzt für alle Server ohne Revisionsdatum ein zufälliges Datum in den nächsten 12 Monaten.
     */
    public function setRevisionDates()
    {
        $servers = Server::whereNull('revision_date')->get();
        $count   = 0;

        foreach ($servers as $server) {
            $server->update([
                'revision_date' => now()->addDays(rand(1, 365)),
            ]);
            $count++;
        }

        $this->auditLogger->logModuleAction('Server', 'Revisionsdaten gesetzt', ['count' => $count]);

        return redirect()->route('server.index')
            ->with('success', "Revisionsdatum für {$count} Server gesetzt.");
    }

    /**
     * Löst per DNS die IP-Adressen für alle Server mit Hostname auf.
     */
    public function resolveIps()
    {
        $count = app(ServerSyncService::class)->resolveIpAddresses();

        $this->auditLogger->logModuleAction('Server', 'IPs per DNS aufgelöst', ['count' => $count]);

        return redirect()->route('server.index')
            ->with('success', "{$count} IP-Adresse(n) per DNS aufgelöst.");
    }

    /**
     * Lädt Network-Modul-Daten (VLAN + Online-Status) für eine Liste von IPs.
     * Gibt eine leere Collection zurück wenn das Network-Modul nicht verfügbar ist.
     *
     * @param  string[]  $ips
     * @return \Illuminate\Support\Collection  Keyed by ip_address
     */
    private function loadNetworkData(array $ips): \Illuminate\Support\Collection
    {
        if (empty($ips) || !class_exists(\App\Modules\Network\Models\IpAddress::class)) {
            return collect();
        }

        try {
            return \App\Modules\Network\Models\IpAddress::with('vlan')
                ->whereIn('ip_address', $ips)
                ->get()
                ->keyBy('ip_address');
        } catch (\Exception) {
            return collect();
        }
    }

    // ─── Hilfsmethoden ────────────────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'abteilungen'   => Abteilung::orderBy('sort_order')->orderBy('name')->get(),
            'users'         => User::where('is_active', true)->orderBy('name')->get(),
            'gruppen'       => Gruppe::orderBy('name')->get(),
            'applikationen' => Applikation::orderBy('name')->get(),
            'osTypes'       => ServerOption::category('os_type')->get(),
            'roles'         => ServerOption::category('role')->get(),
            'backupLevels'  => ServerOption::category('backup_level')->get(),
            'patchRings'    => ServerOption::category('patch_ring')->get(),
            'statusOptions' => Server::STATUS_LABELS,
            'typeOptions'   => Server::TYPE_LABELS,
        ];
    }

    private function validateServer(Request $request): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'dns_hostname'     => ['nullable', 'string', 'max:255'],
            'checkmk_alias'    => ['nullable', 'string', 'max:255'],
            'ip_address'       => ['nullable', 'string', 'max:45'],
            'operating_system' => ['nullable', 'string', 'max:255'],
            'os_version'       => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],
            'bemerkungen'      => ['nullable', 'string'],
            'doc_url'          => ['nullable', 'url', 'max:500'],
            'status'           => ['required', 'in:produktiv,testsystem,ausgeschaltet,im_aufbau,ausgemustert'],
            'type'             => ['nullable', 'in:vm,bare_metal'],
            'os_type_id'       => ['nullable', 'integer', 'exists:server_options,id'],
            'role_id'          => ['nullable', 'integer', 'exists:server_options,id'],
            'backup_level_id'  => ['nullable', 'integer', 'exists:server_options,id'],
            'patch_ring_id'    => ['nullable', 'integer', 'exists:server_options,id'],
            'abteilung_id'     => ['nullable', 'integer', 'exists:abteilungen,id'],
            'admin_user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'gruppe_id'        => ['nullable', 'integer', 'exists:gruppen,id'],
            'applikation_ids'   => ['nullable', 'array'],
            'applikation_ids.*' => ['integer', 'exists:applikationen,id'],
        ]);
    }
}
