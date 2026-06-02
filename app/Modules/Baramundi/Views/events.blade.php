<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('baramundi.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
                <h2 class="font-semibold text-xl text-gray-800">Baramundi – Ereignislog</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form action="{{ route('baramundi.events') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Paket</label>
                        <select name="package_id" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Pakete</option>
                            @foreach($packages as $id => $name)
                                <option value="{{ $id }}" @selected($filterPackage == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Ereignistyp</label>
                        <select name="event_type" onchange="this.form.submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Typen</option>
                            @foreach($eventTypes as $val => $label)
                                <option value="{{ $val }}" @selected($filterEventType === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($filterPackage || $filterEventType)
                        <a href="{{ route('baramundi.events') }}"
                           class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Zurücksetzen
                        </a>
                    @endif
                </form>
            </div>

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitpunkt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
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
                                    <td class="px-4 py-2 text-sm">
                                        @if($event->package)
                                            <a href="{{ route('baramundi.packages.show', $event->package) }}"
                                               class="text-indigo-600 hover:text-indigo-800">{{ $event->package->name }}</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $event->getTypeColor() }}">
                                            {{ $event->getTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-xs font-mono text-gray-700">
                                        {{ $event->version ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-600 max-w-md">{{ $event->message }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">Keine Ereignisse gefunden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($events->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ $events->total() }} Einträge gesamt</span>
                        {{ $events->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
