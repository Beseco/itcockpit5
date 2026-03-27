<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Serververwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Filter + Aktionsleiste --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 p-4">
                <form action="{{ route('server.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">

                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Name, Hostname, IP, Beschreibung…"
                               class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="filter_status"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Status</option>
                            @foreach (\App\Modules\Server\Models\Server::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" @selected($filterStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Abteilung</label>
                        <select name="filter_abteilung_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Abteilungen</option>
                            @foreach ($abteilungen as $abt)
                                <option value="{{ $abt->id }}" @selected($filterAbt == $abt->id)>{{ $abt->anzeigename }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Herkunft</label>
                        <select name="filter_ldap"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle</option>
                            <option value="synced"  @selected($filterLdap === 'synced')>LDAP-synchronisiert</option>
                            <option value="manual"  @selected($filterLdap === 'manual')>Manuell angelegt</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filtern
                        </button>
                        @if ($search || $filterStatus || $filterAbt || $filterLdap)
                            <a href="{{ route('server.index') }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Zurücksetzen
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabelle --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Server
                            @if ($servers->total() > $servers->count())
                                <span class="text-sm font-normal text-gray-400">({{ $servers->total() }} gesamt)</span>
                            @endif
                        </h3>
                        <div class="flex gap-2">
                            @can('server.sync')
                                <form action="{{ route('server.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-2 bg-amber-500 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-amber-600">
                                        LDAP Sync
                                    </button>
                                </form>
                            @endcan
                            @can('server.config')
                                <a href="{{ route('server.settings') }}"
                                   class="inline-flex items-center px-3 py-2 bg-gray-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700">
                                    Einstellungen
                                </a>
                            @endcan
                            @can('server.create')
                                <a href="{{ route('server.create') }}"
                                   class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Neuer Server
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name / Hostname</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OS / Typ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Abteilung</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Herkunft</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($servers as $server)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('server.show', $server) }}"
                                               class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                                {{ $server->name }}
                                            </a>
                                            @if ($server->dns_hostname)
                                                <div class="text-xs text-gray-400">{{ $server->dns_hostname }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $server->ip_address ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                                {{ \App\Modules\Server\Models\Server::STATUS_COLORS[$server->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ \App\Modules\Server\Models\Server::STATUS_LABELS[$server->status] ?? $server->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $server->osType?->label ?? $server->operating_system ?? '—' }}
                                            @if ($server->type)
                                                <span class="ml-1 text-xs text-gray-400">
                                                    ({{ \App\Modules\Server\Models\Server::TYPE_LABELS[$server->type] }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $server->abteilung?->anzeigename ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $server->adminUser?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($server->ldap_synced)
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">LDAP</span>
                                            @else
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Manuell</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <a href="{{ route('server.show', $server) }}"
                                               class="text-indigo-600 hover:text-indigo-900 mr-3">Detail</a>
                                            @can('server.edit')
                                                <a href="{{ route('server.edit', $server) }}"
                                                   class="text-yellow-600 hover:text-yellow-900 mr-3">Bearbeiten</a>
                                            @endcan
                                            @can('server.delete')
                                                <span x-data="{ open: false }">
                                                    <button @click="open = true"
                                                            class="text-red-600 hover:text-red-900">Löschen</button>
                                                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition>
                                                        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Server löschen?</h3>
                                                            <p class="text-sm text-gray-600 mb-4">
                                                                „<strong>{{ $server->name }}</strong>" wird unwiderruflich gelöscht.
                                                            </p>
                                                            <div class="flex justify-end gap-3">
                                                                <button @click="open = false"
                                                                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                                                                <form action="{{ route('server.destroy', $server) }}" method="POST">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit"
                                                                            class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md">Löschen</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </span>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                            Keine Server gefunden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($servers->hasPages())
                        <div class="mt-4">{{ $servers->links() }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
