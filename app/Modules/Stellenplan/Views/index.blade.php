<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellenplan</h2>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">
                    @php $total = $gruppen->flatMap->stellen->count(); $frei = $gruppen->flatMap->stellen->filter->isFrei()->count(); @endphp
                    {{ $total }} Stellen gesamt · <span class="text-amber-600 font-medium">{{ $frei }} FREI</span>
                </span>
                @can('base.stellen.edit')
                    <a href="{{ route('stellen.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Neue Stelle
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{ deleteId: null, deleteName: '' }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        @foreach($gruppen as $gruppe)
            @if($gruppe->stellen->isNotEmpty())
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100">
                    <h3 class="text-sm font-semibold text-indigo-800">{{ $gruppe->name }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellen-Nr.</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellenbezeichnung</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stelleninhaber</th>
                                @if($canSeeSensitive)
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">HH-Bewertung</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bes.-Gruppe</th>
                                @endif
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Belegung %</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Gesamt-Az. %</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Anteil %</th>
                                @can('base.stellen.edit')<th class="px-3 py-2 w-8"></th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($gruppe->stellen as $stelle)
                                <tr class="{{ $stelle->isFrei() ? 'bg-gray-50 text-gray-400' : 'hover:bg-gray-50' }}">
                                    <td class="px-3 py-2.5 font-mono text-xs {{ $stelle->isFrei() ? 'text-gray-400' : 'text-gray-700' }}">
                                        {{ $stelle->stellennummer }}
                                    </td>
                                    <td class="px-3 py-2.5 {{ $stelle->isFrei() ? 'text-gray-400' : 'text-gray-900 font-medium' }}">
                                        {{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if($stelle->isFrei())
                                            <span class="text-xs font-semibold text-amber-500">FREI</span>
                                        @else
                                            {{ $stelle->stelleninhaber->name }}
                                        @endif
                                    </td>
                                    @if($canSeeSensitive)
                                    <td class="px-3 py-2.5 text-gray-600">{{ $stelle->haushalt_bewertung ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-gray-600">{{ $stelle->bes_gruppe ?? '—' }}</td>
                                    @endif
                                    <td class="px-3 py-2.5 text-center text-gray-600">
                                        {{ $stelle->belegung !== null ? number_format($stelle->belegung, 0).' %' : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center text-gray-600">
                                        {{ $stelle->gesamtarbeitszeit !== null ? number_format($stelle->gesamtarbeitszeit, 0).' %' : '—' }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center text-gray-600">
                                        {{ $stelle->anteil_stelle !== null ? number_format($stelle->anteil_stelle, 0).' %' : '—' }}
                                    </td>
                                    @can('base.stellen.edit')
                                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('stellen.edit', $stelle) }}"
                                               class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="Bearbeiten">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <button @click="deleteId = {{ $stelle->id }}; deleteName = '{{ addslashes($stelle->stellenbeschreibung?->bezeichnung ?? $stelle->stellennummer) }}'"
                                                    title="Löschen"
                                                    class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        @endforeach

        {{-- Stellen ohne Gruppe --}}
        @php
            $ohneGruppe = \App\Models\Stelle::whereNull('gruppe_id')
                ->with(['stellenbeschreibung','stelleninhaber'])
                ->orderBy('stellennummer')->get();
        @endphp
        @if($ohneGruppe->isNotEmpty())
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-5 py-3 bg-gray-100 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-600">Ohne Gruppe</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellen-Nr.</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stellenbezeichnung</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stelleninhaber</th>
                            @if($canSeeSensitive)
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">HH-Bewertung</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bes.-Gruppe</th>
                            @endif
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Belegung %</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Gesamt-Az. %</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Anteil %</th>
                            @can('base.stellen.edit')<th class="px-3 py-2 w-8"></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($ohneGruppe as $stelle)
                            <tr class="{{ $stelle->isFrei() ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                <td class="px-3 py-2.5 font-mono text-xs text-gray-700">{{ $stelle->stellennummer }}</td>
                                <td class="px-3 py-2.5 text-gray-900 font-medium">{{ $stelle->stellenbeschreibung?->bezeichnung ?? '—' }}</td>
                                <td class="px-3 py-2.5">
                                    @if($stelle->isFrei())
                                        <span class="text-xs font-semibold text-amber-500">FREI</span>
                                    @else
                                        {{ $stelle->stelleninhaber->name }}
                                    @endif
                                </td>
                                @if($canSeeSensitive)
                                <td class="px-3 py-2.5 text-gray-600">{{ $stelle->haushalt_bewertung ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ $stelle->bes_gruppe ?? '—' }}</td>
                                @endif
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ $stelle->belegung !== null ? number_format($stelle->belegung, 0).' %' : '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ $stelle->gesamtarbeitszeit !== null ? number_format($stelle->gesamtarbeitszeit, 0).' %' : '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ $stelle->anteil_stelle !== null ? number_format($stelle->anteil_stelle, 0).' %' : '—' }}</td>
                                @can('base.stellen.edit')
                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('stellen.edit', $stelle) }}"
                                           class="p-1 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded" title="Bearbeiten">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <button @click="deleteId = {{ $stelle->id }}; deleteName = '{{ addslashes($stelle->stellenbeschreibung?->bezeichnung ?? $stelle->stellennummer) }}'"
                                                title="Löschen"
                                                class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Delete Modal --}}
        <div x-show="deleteId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Stelle löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll <strong x-text="deleteName"></strong> wirklich gelöscht werden? Diese Aktion kann nicht rückgängig gemacht werden.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteId = null; deleteName = ''"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'{{ url('stellen') }}/' + deleteId" method="POST">
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
