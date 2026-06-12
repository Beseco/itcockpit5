<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('baramundi.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
                <h2 class="font-semibold text-xl text-gray-800">{{ $pkg->name }}</h2>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $pkg->getStatusColor() }}">
                    {{ $pkg->getStatusLabel() }}
                </span>
            </div>
            @can('baramundi.edit')
                <a href="{{ route('baramundi.packages.edit', $pkg) }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                    Bearbeiten
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Paket-Infos --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">UNC-Pfad</div>
                        <div class="font-mono text-sm text-gray-800 break-all">{{ $pkg->getUncPath() }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Letzte bekannte Version</div>
                        <div class="text-xl font-bold font-mono text-indigo-600">{{ $pkg->last_known_version ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Letzte Prüfung</div>
                        <div class="text-sm text-gray-800">{{ $pkg->last_scan ? $pkg->last_scan->format('d.m.Y H:i') . ' Uhr' : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Neue Version erkannt am</div>
                        <div class="text-sm text-gray-800">{{ $pkg->last_detected ? $pkg->last_detected->format('d.m.Y H:i') . ' Uhr' : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Download-Methode</div>
                        <div class="text-sm text-gray-800">{{ ucfirst($pkg->download_type) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Optionen</div>
                        <div class="flex gap-3 text-sm">
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full {{ $pkg->enabled ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                {{ $pkg->enabled ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <span class="flex items-center gap-1 text-gray-600">
                                <span class="w-2 h-2 rounded-full {{ $pkg->email_enabled ? 'bg-blue-500' : 'bg-gray-300' }}"></span>
                                {{ $pkg->email_enabled ? 'E-Mail aktiv' : 'E-Mail inaktiv' }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($pkg->notes)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Notizen</div>
                        <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $pkg->notes }}</div>
                    </div>
                @endif

                {{-- Manueller Scan --}}
                @if($pkg->enabled)
                    <div class="mt-4 pt-4 border-t border-gray-100"
                         x-data="{
                             scanning: false, scanResult: null, scanOk: true,
                             async runScan() {
                                 this.scanning = true; this.scanResult = null;
                                 try {
                                     const r = await fetch('{{ route('baramundi.packages.scan', $pkg) }}', {
                                         method: 'POST',
                                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                     });
                                     const j = await r.json();
                                     this.scanOk = j.success;
                                     this.scanResult = j.message;
                                     if (j.success) setTimeout(() => window.location.reload(), 1200);
                                 } catch(e) { this.scanOk = false; this.scanResult = e.toString(); }
                                 finally { this.scanning = false; }
                             }
                         }">
                        <div class="flex items-center gap-4">
                            <button @click="runScan()" :disabled="scanning"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="scanning ? 'Scannt …' : 'Jetzt manuell scannen'">Jetzt manuell scannen</span>
                            </button>
                            <div x-show="scanResult !== null"
                                 :class="scanOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                                 class="border rounded-md px-3 py-1.5 text-sm flex items-center gap-2" x-cloak>
                                <span x-text="scanOk ? '✓' : '✗'" class="font-bold"></span>
                                <span x-text="scanResult"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Ereignislog --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700">Ereignislog</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitpunkt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Meldung</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($events as $event)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $event->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $event->getTypeColor() }}">
                                            {{ $event->getTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-xs font-mono text-gray-700">
                                        {{ $event->version ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $event->message }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">Noch keine Ereignisse.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($events->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
