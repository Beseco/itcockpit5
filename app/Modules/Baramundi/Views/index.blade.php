<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Baramundi – Paketüberwachung</h2>
            <div class="flex items-center gap-3">
                @can('baramundi.edit')
                    <a href="{{ route('baramundi.packages.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        + Paket hinzufügen
                    </a>
                @endcan
                @can('baramundi.config')
                    <a href="{{ route('baramundi.settings') }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Einstellungen
                    </a>
                @endcan
                <a href="{{ route('baramundi.events') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Ereignislog
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form action="{{ route('baramundi.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Name, Server, Pfad …"
                               class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Status</option>
                            @foreach($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected($filterStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filtern
                        </button>
                        @if($search || $filterStatus)
                            <a href="{{ route('baramundi.index') }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Zurücksetzen
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server / Pfad</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letzte Version</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letzte Prüfung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letzte Änderung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($packages as $pkg)
                                <tr class="hover:bg-gray-50" x-data="baraRow(
                                        {{ $pkg->id }},
                                        '{{ route('baramundi.packages.scan', $pkg) }}',
                                        '{{ addslashes($pkg->last_known_version ?? '—') }}',
                                        '{{ $pkg->last_scan ? $pkg->last_scan->format('d.m.Y H:i') : '—' }}',
                                        '{{ addslashes($pkg->getStatusLabel()) }}',
                                        '{{ addslashes($pkg->getStatusColor()) }}'
                                    )">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $pkg->enabled ? 'bg-green-500' : 'bg-gray-300' }}"
                                                  title="{{ $pkg->enabled ? 'Aktiv' : 'Inaktiv' }}"></span>
                                            <div>
                                                <a href="{{ route('baramundi.packages.show', $pkg) }}"
                                                   class="font-medium text-gray-900 hover:text-indigo-600 text-sm">{{ $pkg->name }}</a>
                                                @if($pkg->download_type !== 'none')
                                                    <div class="text-xs text-gray-400">Download: {{ $pkg->download_type }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-700">{{ $pkg->server_name }}</div>
                                        <div class="text-xs text-gray-400 font-mono break-all max-w-xs">{{ $pkg->share_path }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span x-text="lastVersion" class="text-sm font-mono font-medium text-gray-800"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span x-text="lastScan" class="text-xs text-gray-500"></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ $pkg->last_detected ? $pkg->last_detected->format('d.m.Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span x-html="statusBadge" class="inline-flex"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Schnellscan --}}
                                            @if($pkg->enabled)
                                                <button @click="runScan()"
                                                        :disabled="scanning"
                                                        class="text-xs text-indigo-600 hover:text-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-text="scanning ? 'Scannt…' : 'Jetzt scannen'"></span>
                                                </button>
                                            @endif
                                            @can('baramundi.edit')
                                                <a href="{{ route('baramundi.packages.edit', $pkg) }}"
                                                   class="text-xs text-gray-500 hover:text-gray-700">Bearbeiten</a>
                                                <span x-data="{ open: false }">
                                                    <button @click="open = true" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition style="display:none">
                                                        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Paket löschen?</h3>
                                                            <p class="text-sm text-gray-600 mb-4">
                                                                „<strong>{{ $pkg->name }}</strong>" und alle zugehörigen Ereignisse werden unwiderruflich gelöscht.
                                                            </p>
                                                            <div class="flex justify-end gap-3">
                                                                <button @click="open = false" class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                                                                <form action="{{ route('baramundi.packages.destroy', $pkg) }}" method="POST">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md">Löschen</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </span>
                                            @endcan
                                        </div>
                                        <div x-show="scanMessage" class="text-xs mt-1 text-right"
                                             :class="scanOk ? 'text-green-600' : 'text-red-600'"
                                             x-text="scanMessage" style="display:none"></div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                                        Keine Pakete konfiguriert.
                                        @can('baramundi.edit')
                                            <a href="{{ route('baramundi.packages.create') }}" class="text-indigo-600 hover:underline ml-1">Erstes Paket anlegen</a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
    function baraRow(pkgId, scanUrl, initVersion, initScan, initStatusLabel, initStatusColor) {
        function makeBadge(label, color) {
            return `<span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full ${color}">${label}</span>`;
        }
        return {
            scanning:    false,
            scanMessage: null,
            scanOk:      true,
            lastVersion: initVersion,
            lastScan:    initScan,
            statusBadge: makeBadge(initStatusLabel, initStatusColor),

            async runScan() {
                this.scanning = true;
                this.scanMessage = null;
                try {
                    const r = await fetch(scanUrl, {
                        method:  'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept':       'application/json',
                        },
                    });
                    const j = await r.json();
                    this.scanOk      = j.success;
                    this.scanMessage = j.message;
                    if (j.success) {
                        this.lastVersion = j.last_known_version;
                        this.lastScan    = j.last_scan;
                        this.statusBadge = makeBadge(j.status_label, j.status_color);
                    }
                } catch(e) {
                    this.scanOk      = false;
                    this.scanMessage = 'Fehler: ' + e.toString();
                } finally {
                    this.scanning = false;
                }
            }
        };
    }
    </script>
</x-app-layout>
