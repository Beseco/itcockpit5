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

            {{-- Filter --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 p-4">
                <form action="{{ route('server.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">

                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Name, Hostname, IP…"
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
                        <label class="block text-xs font-medium text-gray-500 mb-1">Admin</label>
                        <select name="filter_admin_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Admins</option>
                            <option value="__mine__"    @selected($filterAdminId === '__mine__')>Nur meine Server</option>
                            <option value="__none__"    @selected($filterAdminId === '__none__')>Kein Admin hinterlegt</option>
                            @foreach ($adminUsers as $u)
                                <option value="{{ $u->id }}" @selected($filterAdminId == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Herkunft</label>
                        <select name="filter_ldap"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle</option>
                            <option value="synced" @selected($filterLdap === 'synced')>LDAP-synchronisiert</option>
                            <option value="manual" @selected($filterLdap === 'manual')>Manuell angelegt</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pb-0.5">
                        <input type="checkbox" id="filter_no_revision" name="filter_no_revision" value="1"
                               @checked($filterNoRevision)
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="filter_no_revision" class="text-sm text-gray-700 cursor-pointer select-none whitespace-nowrap">Kein Revisionsdatum</label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filtern
                        </button>
                        @if ($search || $filterStatus || $filterAbt || $filterLdap || $filterAdminId || $filterNoRevision)
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
                    <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Server
                            @if ($servers->total() > $servers->count())
                                <span class="text-sm font-normal text-gray-400">({{ $servers->total() }} gesamt)</span>
                            @endif
                        </h3>
                        <div class="flex gap-2 flex-wrap">
                            @can('server.edit')
                                @if ($countNoRevision > 0)
                                    <form action="{{ route('server.set-revision-dates') }}" method="POST"
                                          onsubmit="return confirm('Für {{ $countNoRevision }} Server ein zufälliges Revisionsdatum (nächste 12 Monate) setzen?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-2 bg-orange-500 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-orange-600">
                                            Revisionsdaten setzen ({{ $countNoRevision }})
                                        </button>
                                    </form>
                                @endif
                            @endcan
                            @can('server.edit')
                                <form action="{{ route('server.resolve-ips') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-2 bg-teal-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-teal-700">
                                        IPs per DNS auflösen
                                    </button>
                                </form>
                            @endcan
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
                                    + Neuer Server
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name / Hostname</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP / Netzwerk</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OS / Typ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revision</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($servers as $server)
                                    @php $revisionOverdue = $server->revision_date && $server->revision_date->isPast(); @endphp
                                    <tr class="hover:bg-gray-50 {{ $revisionOverdue ? 'border-l-4 border-l-red-400' : '' }}">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('server.show', $server) }}"
                                               class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                                {{ $server->name }}
                                            </a>
                                            @if ($server->dns_hostname)
                                                <div class="text-xs text-gray-400">{{ $server->dns_hostname }}</div>
                                            @endif
                                            @if ($server->ldap_synced)
                                                <span class="text-xs text-blue-400">LDAP</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @php $net = $server->ip_address ? ($networkData[$server->ip_address] ?? null) : null; @endphp
                                            @if ($server->ip_address)
                                                <div class="flex items-center gap-1.5">
                                                    @if ($net)
                                                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $net->is_online ? 'bg-green-500' : 'bg-gray-300' }}"
                                                              title="{{ $net->is_online ? 'Online' : 'Offline' }}"></span>
                                                    @endif
                                                    <span class="text-gray-700 font-mono text-xs">{{ $server->ip_address }}</span>
                                                </div>
                                                @if ($net?->vlan)
                                                    <div class="text-xs text-blue-500 mt-0.5">{{ $net->vlan->vlan_name }}</div>
                                                @endif
                                            @else
                                                <span class="text-gray-300 text-xs">—</span>
                                            @endif
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
                                                <span class="text-xs text-gray-400">
                                                    ({{ \App\Modules\Server\Models\Server::TYPE_LABELS[$server->type] }})
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($server->adminUser)
                                                <span class="{{ $server->admin_user_id === Auth::id() ? 'font-semibold text-indigo-700' : 'text-gray-700' }}">
                                                    {{ $server->adminUser->name }}
                                                </span>
                                            @else
                                                <span class="text-red-400 text-xs">Kein Admin</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($server->revision_date)
                                                <span class="{{ $revisionOverdue ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                                    {{ $server->revision_date->format('d.m.Y') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1 items-center">
                                            {{-- Detail --}}
                                            <a href="{{ route('server.show', $server) }}"
                                               class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded transition-colors"
                                               title="Detail">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            @can('server.edit')
                                            {{-- Bearbeiten --}}
                                            <a href="{{ route('server.edit', $server) }}"
                                               class="inline-flex items-center justify-center w-8 h-8 text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50 rounded transition-colors"
                                               title="Bearbeiten">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @endcan
                                            @can('server.delete')
                                                <span x-data="{ open: false }">
                                                    {{-- Löschen --}}
                                                    <button @click="open = true"
                                                            class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                                            title="Löschen">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition style="display:none">
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
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
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
