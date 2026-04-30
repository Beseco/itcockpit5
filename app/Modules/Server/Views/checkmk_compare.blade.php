<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">CheckMK-Abgleich</h2>
            <a href="{{ route('server.index') }}"
               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50">
                ← Zurück zur Übersicht
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (!empty($error))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-4 rounded-lg">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold">Konfigurationsfehler</span>
                    </div>
                    <p class="text-sm">{{ $error }}</p>
                    @can('server.config')
                        <a href="{{ route('server.settings') }}" class="mt-2 inline-block text-sm underline">Zu den Einstellungen</a>
                    @endcan
                </div>
            @else

            {{-- Sektion 1: In CheckMK, nicht in IT-Cockpit --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">In CheckMK – nicht in IT-Cockpit</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $onlyInCheckMk->count() }} Gerät(e) in CheckMK gefunden, die noch nicht in IT-Cockpit angelegt sind.
                        </p>
                    </div>
                    @if($onlyInCheckMk->isNotEmpty())
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-700">
                            {{ $onlyInCheckMk->count() }} fehlend
                        </span>
                    @endif
                </div>

                @if($onlyInCheckMk->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-3 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Alle CheckMK-Geräte sind in IT-Cockpit vorhanden.
                    </div>
                @else
                    <form action="{{ route('server.checkmk.compare.import') }}" method="POST" id="import-form">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">
                                            <input type="checkbox" id="select-all"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                   onclick="document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked)">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name (CheckMK)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alias</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP-Adresse</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100" x-data="importTable()">
                                    @foreach($onlyInCheckMk as $i => $host)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5">
                                            <input type="checkbox" name="hosts[{{ $i }}][_selected]" value="1"
                                                   class="row-check rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <input type="hidden" name="hosts[{{ $i }}][name]"    value="{{ $host['name'] }}">
                                            <input type="hidden" name="hosts[{{ $i }}][alias]"   value="{{ $host['alias'] }}">
                                            <input type="hidden" name="hosts[{{ $i }}][address]" value="{{ $host['address'] }}">
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-mono text-gray-900">{{ $host['name'] }}</td>
                                        <td class="px-4 py-2.5 text-sm text-gray-600">{{ $host['alias'] ?: '—' }}</td>
                                        <td class="px-4 py-2.5 text-sm text-gray-600 font-mono">{{ $host['address'] ?: '—' }}</td>
                                        <td class="px-4 py-2.5">
                                            <select name="hosts[{{ $i }}][type]"
                                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-1">
                                                @foreach(\App\Modules\Server\Models\Server::TYPE_LABELS as $val => $label)
                                                    <option value="{{ $val }}" @selected($val === $host['suggested_type'])>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-500">Nur markierte Geräte werden importiert. Typ kann pro Gerät angepasst werden.</p>
                            <button type="submit"
                                    onclick="
                                        const checked = document.querySelectorAll('.row-check:checked');
                                        if (checked.length === 0) { alert('Bitte mindestens ein Gerät auswählen.'); return false; }
                                        // Disable unchecked rows so they're not submitted
                                        document.querySelectorAll('.row-check:not(:checked)').forEach(cb => {
                                            cb.closest('tr').querySelectorAll('input,select').forEach(el => el.disabled = true);
                                        });
                                        return confirm(checked.length + ' Gerät(e) importieren?');
                                    "
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                Ausgewählte importieren
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Sektion 2: In IT-Cockpit, kein CheckMK-Monitoring --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">In IT-Cockpit – kein CheckMK-Monitoring</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $notMonitored->count() }} Gerät(e) in IT-Cockpit sind nicht in CheckMK überwacht.
                        </p>
                    </div>
                    @if($notMonitored->isNotEmpty())
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-700">
                            {{ $notMonitored->count() }} nicht überwacht
                        </span>
                    @endif
                </div>

                @if($notMonitored->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-3 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Alle IT-Cockpit-Geräte sind in CheckMK überwacht.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hostname / CheckMK-Alias</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($notMonitored as $server)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ $server->name }}</td>
                                    <td class="px-4 py-2.5 text-sm text-gray-500 font-mono">
                                        {{ $server->checkmk_alias ?: ($server->dns_hostname ?: '—') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-gray-500">
                                        {{ \App\Modules\Server\Models\Server::TYPE_LABELS[$server->type] ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @php
                                            $colors = \App\Modules\Server\Models\Server::STATUS_COLORS[$server->status] ?? 'bg-gray-100 text-gray-600';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors }}">
                                            {{ \App\Modules\Server\Models\Server::STATUS_LABELS[$server->status] ?? $server->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('server.show', $server) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Details →</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @endif

        </div>
    </div>
</x-app-layout>
