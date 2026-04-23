<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('server.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $server->name }}</h2>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                {{ \App\Modules\Server\Models\Server::STATUS_COLORS[$server->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ \App\Modules\Server\Models\Server::STATUS_LABELS[$server->status] ?? $server->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Aktionsleiste --}}
            <div class="flex gap-2 justify-end flex-wrap">
                @can('server.edit')
                    <form action="{{ route('server.revision-done', $server) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            ✓ Revision durchgeführt
                        </button>
                    </form>
                    <a href="{{ route('server.edit', $server) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Bearbeiten
                    </a>
                @endcan
            </div>

            {{-- Stammdaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Stammdaten</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="font-medium text-gray-500">Name:</span> <span class="ml-2 text-gray-900">{{ $server->name }}</span></div>
                    <div><span class="font-medium text-gray-500">DNS-Hostname:</span> <span class="ml-2 text-gray-900">{{ $server->dns_hostname ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">IP-Adresse:</span> <span class="ml-2 text-gray-900">{{ $server->ip_address ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Betriebssystem:</span> <span class="ml-2 text-gray-900">{{ $server->operating_system ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">OS-Version:</span> <span class="ml-2 text-gray-900">{{ $server->os_version ?? '—' }}</span></div>
                    <div>
                        <span class="font-medium text-gray-500">Revisionsdatum:</span>
                        @if ($server->revision_date)
                            <span class="ml-2 {{ $server->revision_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                                {{ $server->revision_date->format('d.m.Y') }}
                                @if ($server->revision_date->isPast())
                                    <span class="text-xs font-normal">(überfällig)</span>
                                @endif
                            </span>
                        @else
                            <span class="ml-2 text-gray-400">—</span>
                        @endif
                    </div>
                    @if ($server->doc_url)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-500">Dokumentation:</span>
                            <a href="{{ $server->doc_url }}" target="_blank" rel="noopener"
                               class="ml-2 text-indigo-600 hover:underline break-all">{{ $server->doc_url }}</a>
                        </div>
                    @endif
                    @if ($server->description)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-500">Beschreibung:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $server->description }}</p>
                        </div>
                    @endif
                    @if ($server->bemerkungen)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-500">Bemerkungen:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $server->bemerkungen }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- CheckMK Monitoring --}}
            @if(\App\Modules\Server\Models\CheckMkSettings::getSingleton()->isConfigured())
            <script>
            function checkmkCard(url) {
                return {
                    url,
                    loading: false,
                    loaded: false,
                    error: null,
                    data: {},
                    load() {
                        this.loading = true;
                        this.error = null;
                        fetch(this.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(r => r.ok ? r.json() : r.json().then(j => Promise.reject(j.error || 'HTTP ' + r.status)))
                            .then(json => {
                                if (json.error) { this.error = json.error; }
                                else { this.data = json; }
                                this.loaded = true;
                            })
                            .catch(err => { this.error = String(err); this.loaded = true; })
                            .finally(() => { this.loading = false; });
                    },
                    isStorage(name) {
                        return /filesystem|disk/i.test(name);
                    },
                    usagePercent(output) {
                        const m = output.match(/(\d+(?:\.\d+)?)\s*%/);
                        return m ? parseFloat(m[1]) : null;
                    },
                    barColor(pct) {
                        if (pct >= 90) return 'bg-red-500';
                        if (pct >= 80) return 'bg-yellow-400';
                        return 'bg-green-500';
                    }
                };
            }
            </script>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"
                 x-data="checkmkCard('{{ route('server.checkmk.data', $server) }}')"
                 x-init="load()">

                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">CheckMK Monitoring</h3>
                    <div class="flex items-center gap-3">
                        <span x-show="loaded && !error" class="text-xs text-gray-400"
                              x-text="'Host: ' + (data.hostname || '—')"></span>
                        <button @click="load()" :disabled="loading"
                                class="text-xs text-indigo-500 hover:text-indigo-700 disabled:opacity-40">
                            ↺ Aktualisieren
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Ladezustand --}}
                    <div x-show="loading" class="flex items-center gap-2 text-sm text-gray-400">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Daten werden geladen…
                    </div>

                    {{-- Fehler --}}
                    <div x-show="error && !loading" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2"
                         x-text="error"></div>

                    {{-- Daten --}}
                    <div x-show="loaded && !error && !loading" x-cloak class="space-y-4">

                        {{-- Host-Status (Ping) --}}
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-500 w-32 shrink-0">Erreichbarkeit</span>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-700': data.state === 0,
                                      'bg-red-100 text-red-700':    data.state === 1 || data.state === 2,
                                      'bg-gray-100 text-gray-500':  data.state === null || data.state === undefined
                                  }"
                                  x-text="data.state_label || '—'"></span>
                        </div>

                        {{-- Services --}}
                        <template x-if="data.services && data.services.length > 0">
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Service</th>
                                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Status</th>
                                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="svc in data.services" :key="svc.name">
                                            <tr class="border-t border-gray-100">
                                                <td class="px-4 py-2.5 font-medium text-gray-800" x-text="svc.name"></td>
                                                <td class="px-4 py-2.5">
                                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                                          :class="{
                                                              'bg-green-100 text-green-700':  svc.state === 0,
                                                              'bg-yellow-100 text-yellow-700': svc.state === 1,
                                                              'bg-red-100 text-red-700':      svc.state === 2,
                                                              'bg-gray-100 text-gray-500':    svc.state === 3 || svc.state === null
                                                          }"
                                                          x-text="svc.state_label"></span>
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-600 text-xs">
                                                    <template x-if="isStorage(svc.name) && usagePercent(svc.plugin_output) !== null">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center gap-2">
                                                                <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                                                    <div class="h-2.5 rounded-full transition-all"
                                                                         :class="barColor(usagePercent(svc.plugin_output))"
                                                                         :style="'width:' + Math.min(usagePercent(svc.plugin_output), 100) + '%'"></div>
                                                                </div>
                                                                <span class="text-xs font-semibold w-10 text-right shrink-0"
                                                                      :class="{
                                                                          'text-red-600':    usagePercent(svc.plugin_output) >= 90,
                                                                          'text-yellow-600': usagePercent(svc.plugin_output) >= 80 && usagePercent(svc.plugin_output) < 90,
                                                                          'text-green-600':  usagePercent(svc.plugin_output) < 80
                                                                      }"
                                                                      x-text="usagePercent(svc.plugin_output).toFixed(1) + '%'"></span>
                                                            </div>
                                                            <div x-text="svc.plugin_output || '—'" class="text-gray-500 text-xs leading-tight"></div>
                                                        </div>
                                                    </template>
                                                    <template x-if="!isStorage(svc.name) || usagePercent(svc.plugin_output) === null">
                                                        <span x-text="svc.plugin_output || '—'"></span>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="data.services && data.services.length === 0">
                            <p class="text-sm text-gray-400">Keine relevanten Services (CPU, RAM, Disk, Uptime) in CheckMK gefunden.</p>
                        </template>
                    </div>
                </div>
            </div>
            @endif

            {{-- Klassifizierung --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Klassifizierung</h3>
                </div>
                <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-500">Status:</span>
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full
                            {{ \App\Modules\Server\Models\Server::STATUS_COLORS[$server->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ \App\Modules\Server\Models\Server::STATUS_LABELS[$server->status] ?? $server->status }}
                        </span>
                    </div>
                    <div><span class="font-medium text-gray-500">Typ:</span>
                        <span class="ml-2 text-gray-900">
                            {{ $server->type ? \App\Modules\Server\Models\Server::TYPE_LABELS[$server->type] : '—' }}
                        </span>
                    </div>
                    <div><span class="font-medium text-gray-500">OS-Typ:</span> <span class="ml-2 text-gray-900">{{ $server->osType?->label ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Rolle:</span> <span class="ml-2 text-gray-900">{{ $server->role?->label ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Backup:</span> <span class="ml-2 text-gray-900">{{ $server->backupLevel?->label ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Patch-Ring:</span> <span class="ml-2 text-gray-900">{{ $server->patchRing?->label ?? '—' }}</span></div>
                </div>
            </div>

            {{-- Zuständigkeiten --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Zuständigkeiten</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div><span class="font-medium text-gray-500">Abteilung:</span> <span class="ml-2 text-gray-900">{{ $server->abteilung?->anzeigename ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Admin:</span> <span class="ml-2 text-gray-900">{{ $server->adminUser?->name ?? '—' }}</span></div>
                    <div><span class="font-medium text-gray-500">Gruppe:</span> <span class="ml-2 text-gray-900">{{ $server->gruppe?->name ?? '—' }}</span></div>
                </div>
            </div>

            {{-- Netzwerk-Info (aus Network-Modul) --}}
            @if ($networkEntry)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Netzwerk</h3>
                        <span class="flex items-center gap-1.5 text-sm font-medium {{ $networkEntry->is_online ? 'text-green-600' : 'text-gray-400' }}">
                            <span class="w-2.5 h-2.5 rounded-full {{ $networkEntry->is_online ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                            {{ $networkEntry->is_online ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-500">IP-Adresse:</span>
                            <span class="ml-2 font-mono text-gray-900">{{ $networkEntry->ip_address }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500">VLAN:</span>
                            <span class="ml-2 text-gray-900">
                                {{ $networkEntry->vlan?->vlan_name ?? '—' }}
                                @if ($networkEntry->vlan)
                                    <span class="text-gray-400 text-xs">({{ $networkEntry->vlan->network_address }})</span>
                                @endif
                            </span>
                        </div>
                        @if ($networkEntry->ping_ms)
                            <div>
                                <span class="font-medium text-gray-500">Ping:</span>
                                <span class="ml-2 text-gray-900">{{ number_format($networkEntry->ping_ms, 1) }} ms</span>
                            </div>
                        @endif
                        @if ($networkEntry->last_scanned_at)
                            <div>
                                <span class="font-medium text-gray-500">Letzter Scan:</span>
                                <span class="ml-2 text-gray-900">{{ $networkEntry->last_scanned_at->diffForHumans() }}</span>
                            </div>
                        @endif
                        @if ($networkEntry->mac_address)
                            <div>
                                <span class="font-medium text-gray-500">MAC:</span>
                                <span class="ml-2 font-mono text-gray-700 text-xs">{{ $networkEntry->mac_address }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($server->ip_address)
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-5 py-3 text-sm text-gray-500">
                    IP {{ $server->ip_address }} ist nicht im Network-Modul erfasst – noch kein Scan durchgeführt.
                </div>
            @endif

            {{-- LDAP-Info --}}
            @if ($server->ldap_synced || $server->distinguished_name)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">LDAP-Information</h3>
                    </div>
                    <div class="p-6 space-y-2 text-sm">
                        <div><span class="font-medium text-gray-500">Synchronisiert:</span>
                            <span class="ml-2">
                                @if ($server->ldap_synced)
                                    <span class="text-blue-600 font-medium">Ja</span>
                                    — letzter Sync: {{ $server->last_sync_at?->format('d.m.Y H:i') ?? '—' }}
                                @else
                                    <span class="text-gray-500">Nein / nicht mehr in LDAP</span>
                                @endif
                            </span>
                        </div>
                        @if ($server->distinguished_name)
                            <div><span class="font-medium text-gray-500">Distinguished Name:</span>
                                <span class="ml-2 text-gray-700 font-mono text-xs">{{ $server->distinguished_name }}</span>
                            </div>
                        @endif
                        @if ($server->managed_by_ldap)
                            <div><span class="font-medium text-gray-500">Managed By (LDAP):</span>
                                <span class="ml-2 text-gray-700 text-xs">{{ $server->managed_by_ldap }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- vSphere Hardware --}}
            @if($server->vsphere_synced)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">vSphere Hardware</h3>
                        <span class="text-xs text-gray-400">
                            Sync: {{ $server->vsphere_synced_at?->format('d.m.Y H:i') ?? '—' }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        @if($server->cpu_count)
                            <div>
                                <span class="font-medium text-gray-500">CPU:</span>
                                <span class="ml-2 text-gray-900">{{ $server->cpu_count }} {{ $server->cpu_count === 1 ? 'Kern' : 'Kerne' }}</span>
                            </div>
                        @endif
                        @if($server->memory_mb)
                            <div>
                                <span class="font-medium text-gray-500">RAM:</span>
                                <span class="ml-2 text-gray-900">
                                    @if($server->memory_mb >= 1024)
                                        {{ number_format($server->memory_mb / 1024, 1, ',', '.') }} GB
                                    @else
                                        {{ $server->memory_mb }} MB
                                    @endif
                                </span>
                            </div>
                        @endif
                        @if($server->disk_gb)
                            <div>
                                <span class="font-medium text-gray-500">Speicher:</span>
                                <span class="ml-2 text-gray-900">{{ $server->disk_gb }} GB</span>
                            </div>
                        @endif
                        @if($server->vsphere_datastore)
                            <div>
                                <span class="font-medium text-gray-500">Datastore:</span>
                                <span class="ml-2 text-gray-900 font-mono text-xs">{{ $server->vsphere_datastore }}</span>
                            </div>
                        @endif
                        @if($server->vsphere_vm_id)
                            <div>
                                <span class="font-medium text-gray-500">VM-ID:</span>
                                <span class="ml-2 text-gray-400 font-mono text-xs">{{ $server->vsphere_vm_id }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Verknüpfte Applikationen --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Verknüpfte Applikationen
                        <span class="font-normal text-gray-400">({{ $server->applikationen->count() }})</span>
                    </h3>
                </div>
                <div class="p-6">
                    @forelse ($server->applikationen as $app)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm text-gray-800">{{ $app->name }}</span>
                            @can('applikationen.edit')
                                <a href="{{ route('applikationen.edit', $app) }}"
                                   class="text-xs text-indigo-600 hover:underline">bearbeiten</a>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Keine Applikationen verknüpft.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

