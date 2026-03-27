<?php

namespace App\Modules\Server\Http\Controllers;

use App\Models\Abteilung;
use App\Models\Applikation;
use App\Models\Gruppe;
use App\Models\User;
use App\Modules\Server\Models\Server;
use App\Modules\Server\Models\ServerOption;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ServerController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $search        = $request->get('search', '');
        $filterStatus  = $request->get('filter_status', '');
        $filterAbt     = $request->get('filter_abteilung_id', '');
        $filterLdap    = $request->get('filter_ldap', '');

        $query = Server::with(['abteilung', 'adminUser', 'gruppe', 'osType', 'role', 'backupLevel', 'patchRing'])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dns_hostname', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        if ($filterAbt !== '') {
            $query->where('abteilung_id', (int) $filterAbt);
        }

        if ($filterLdap === 'synced') {
            $query->where('ldap_synced', true);
        } elseif ($filterLdap === 'manual') {
            $query->where('ldap_synced', false);
        }

        $servers     = $query->paginate(25)->withQueryString();
        $abteilungen = Abteilung::orderBy('sort_order')->orderBy('name')->get();

        return view('server::index', compact(
            'servers', 'abteilungen',
            'search', 'filterStatus', 'filterAbt', 'filterLdap'
        ));
    }

    public function show(Server $server)
    {
        $server->load(['abteilung', 'adminUser', 'gruppe', 'osType', 'role', 'backupLevel', 'patchRing', 'applikationen']);
        return view('server::show', compact('server'));
    }

    public function create()
    {
        $server = null;
        return view('server::create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated      = $this->validateServer($request);
        $applikationIds = $request->input('applikation_ids', []);
        unset($validated['applikation_ids']);

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
        $name = $server->name;
        $server->delete();

        $this->auditLogger->logModuleAction('Server', 'Server gelöscht', ['name' => $name]);

        return redirect()->route('server.index')
            ->with('success', 'Server "' . $name . '" wurde gelöscht.');
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
