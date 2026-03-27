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
            <div class="flex gap-2 justify-end">
                @can('server.edit')
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
