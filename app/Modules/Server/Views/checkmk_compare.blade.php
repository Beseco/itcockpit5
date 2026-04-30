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

            {{-- Fehler --}}
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

            {{-- Filter-Karte --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden" x-data="{
                direction: '{{ $direction ?? '' }}',
                get showFolders() { return this.direction === 'checkmk_to_cockpit'; }
            }">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Abgleich konfigurieren</h3>
                </div>
                <form action="{{ route('server.checkmk.compare') }}" method="GET" class="p-6 space-y-5">

                    {{-- Richtung --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vergleichsrichtung</label>
                        <div class="flex flex-col sm:flex-row gap-3">

                            <label class="flex-1 flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="direction === 'checkmk_to_cockpit' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="direction" value="checkmk_to_cockpit"
                                       x-model="direction"
                                       class="mt-0.5 text-purple-600 focus:ring-purple-500">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">CheckMK → IT-Cockpit</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Zeigt Geräte aus CheckMK, die noch nicht in IT-Cockpit angelegt sind. Mit Ordner-Filter.</div>
                                </div>
                            </label>

                            <label class="flex-1 flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition"
                                   :class="direction === 'cockpit_to_checkmk' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="direction" value="cockpit_to_checkmk"
                                       x-model="direction"
                                       class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">IT-Cockpit → CheckMK</div>
                                    <div class="text-xs text-gray-500 mt-0.5">Zeigt IT-Cockpit-Geräte, die in CheckMK nicht überwacht werden.</div>
                                </div>
                            </label>

                        </div>
                    </div>

                    {{-- Ordner-Filter (nur bei checkmk → cockpit) --}}
                    <div x-show="showFolders" x-transition style="display:none">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            CheckMK-Ordner filtern
                            <span class="text-xs font-normal text-gray-400 ml-1">(kein Haken = alle Ordner)</span>
                        </label>
                        @if(empty($folders))
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-400 italic">
                                Keine Ordner aus CheckMK geladen — alle Hosts werden verglichen.
                            </div>
                        @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 p-4 bg-gray-50 border border-gray-200 rounded-lg max-h-56 overflow-y-auto">
                            @foreach($folders as $folder)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="folders[]" value="{{ $folder['path'] }}"
                                       @if(in_array($folder['path'], $selectedFolders)) checked @endif
                                       class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                <span class="text-xs text-gray-700 group-hover:text-gray-900 truncate" title="{{ $folder['label'] }}">
                                    {{ $folder['title'] }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="!direction"
                                class="inline-flex items-center px-5 py-2.5 bg-purple-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Abgleich starten
                        </button>
                    </div>

                </form>
            </div>

            {{-- Ergebnisse --}}
            @if($ran)

            {{-- Sektion: CheckMK → IT-Cockpit --}}
            @if($direction === 'checkmk_to_cockpit')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">In CheckMK – nicht in IT-Cockpit</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $onlyInCheckMk->count() }} Gerät(e) gefunden
                            @if(!empty($selectedFolders))
                                in {{ count($selectedFolders) }} ausgewählten Ordner(n)
                            @endif
                        </p>
                    </div>
                    @if($onlyInCheckMk->isNotEmpty())
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-700">
                            {{ $onlyInCheckMk->count() }} fehlend
                        </span>
                    @endif
                </div>

                @if($onlyInCheckMk->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-400 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-3 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Alle CheckMK-Geräte sind bereits in IT-Cockpit vorhanden.
                    </div>
                @else
                    <form action="{{ route('server.checkmk.compare.import') }}" method="POST">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-10">
                                            <input type="checkbox" id="select-all"
                                                   class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500"
                                                   onclick="document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked)">
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name (CheckMK)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alias</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP-Adresse</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordner</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ (für Import)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @foreach($onlyInCheckMk as $i => $host)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5">
                                            <input type="checkbox" name="hosts[{{ $i }}][_selected]" value="1"
                                                   class="row-check rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                            <input type="hidden" name="hosts[{{ $i }}][name]"    value="{{ $host['name'] }}">
                                            <input type="hidden" name="hosts[{{ $i }}][alias]"   value="{{ $host['alias'] }}">
                                            <input type="hidden" name="hosts[{{ $i }}][address]" value="{{ $host['address'] }}">
                                        </td>
                                        <td class="px-4 py-2.5 text-sm font-mono text-gray-900">{{ $host['name'] }}</td>
                                        <td class="px-4 py-2.5 text-sm text-gray-500">{{ $host['alias'] ?: '—' }}</td>
                                        <td class="px-4 py-2.5 text-sm text-gray-500 font-mono">{{ $host['address'] ?: '—' }}</td>
                                        <td class="px-4 py-2.5 text-xs text-gray-400 font-mono">{{ $host['folder'] ?? '/' }}</td>
                                        <td class="px-4 py-2.5">
                                            <select name="hosts[{{ $i }}][type]"
                                                    class="border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm text-sm py-1">
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
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-3 flex-wrap">
                            <p class="text-xs text-gray-500">Nur markierte Geräte werden importiert. Typ kann pro Gerät angepasst werden.</p>
                            <button type="submit"
                                    onclick="
                                        const checked = document.querySelectorAll('.row-check:checked');
                                        if (checked.length === 0) { alert('Bitte mindestens ein Gerät auswählen.'); return false; }
                                        document.querySelectorAll('.row-check:not(:checked)').forEach(cb => {
                                            cb.closest('tr').querySelectorAll('input,select').forEach(el => el.disabled = true);
                                        });
                                        return confirm(checked.length + ' Gerät(e) importieren?');
                                    "
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-purple-700">
                                Ausgewählte importieren
                            </button>
                        </div>
                    </form>
                @endif
            </div>
            @endif

            {{-- Sektion: IT-Cockpit → CheckMK --}}
            @if($direction === 'cockpit_to_checkmk')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">In IT-Cockpit – kein CheckMK-Monitoring</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $notMonitored->count() }} Gerät(e) ohne Monitoring in CheckMK
                        </p>
                    </div>
                    @if($notMonitored->isNotEmpty())
                        <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-700">
                            {{ $notMonitored->count() }} nicht überwacht
                        </span>
                    @endif
                </div>

                @if($notMonitored->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-400 text-sm">
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
                                        @php $colors = \App\Modules\Server\Models\Server::STATUS_COLORS[$server->status] ?? 'bg-gray-100 text-gray-600'; @endphp
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

            @endif {{-- $ran --}}
            @endif {{-- $error --}}

        </div>
    </div>
</x-app-layout>
