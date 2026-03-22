<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibungen</h2>
            @can('base.stellen.create')
                <a href="{{ route('stellen.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Stelle
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ deleteId: null, deleteBezeichnung: '' }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Bezeichnung</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">St.-Nr.</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gruppe</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">TVöD</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stunden</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stelleninhaber</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gesamt %</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($stellen as $stelle)
                        @php $gesamt = $stelle->gesamtanteil(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $stelle->bezeichnung }}</td>
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $stelle->stellennummer ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $stelle->gruppe?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $stelle->tvod_bewertung ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($stelle->stunden)
                                    {{ number_format($stelle->stunden, 1, ',', '.') }} Std.
                                    <span class="text-xs text-gray-400">({{ $stelle->isVollzeit() ? 'VZ' : 'TZ' }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($stelle->stelleninhaber)
                                    <span class="text-gray-900">{{ $stelle->stelleninhaber->name }}</span>
                                @else
                                    <span class="text-amber-600 text-xs font-medium">unbesetzt</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                    {{ $gesamt === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                                    {{ $gesamt }}%
                                    @if($gesamt !== 100) ⚠️ @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @can('base.stellen.view')
                                    <a href="{{ route('stellen.show', $stelle) }}"
                                       class="inline-flex items-center text-gray-500 hover:text-indigo-600"
                                       title="Ansehen">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">Noch keine Stellen vorhanden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($stellen->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $stellen->links() }}</div>
            @endif
        </div>

        {{-- Delete Modal --}}
        <div x-show="deleteId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Stelle löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll die Stelle <strong x-text="deleteBezeichnung"></strong> wirklich gelöscht werden?
                    Alle Arbeitsvorgänge werden ebenfalls gelöscht.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteId = null"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'/stellen/' + deleteId" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700">
                            Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
