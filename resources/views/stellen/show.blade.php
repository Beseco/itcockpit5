<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('stellen.index') }}"
                   class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibungen</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ showDeleteModal: false }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Linke Spalte: Stammdaten (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Stammdaten-Card --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-900">Stellendaten</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bezeichnung</dt>
                                <dd class="mt-1 text-xl font-bold text-gray-900">{{ $stelle->bezeichnung }}</dd>
                            </div>

                            @if($stelle->stellennummer)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stellennummer</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-700">{{ $stelle->stellennummer }}</dd>
                            </div>
                            @endif

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Gruppe</dt>
                                <dd class="mt-1 text-sm text-gray-700">{{ $stelle->gruppe?->name ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stelleninhaber</dt>
                                <dd class="mt-1">
                                    @if($stelle->stelleninhaber)
                                        <span class="text-sm text-gray-900 font-medium">{{ $stelle->stelleninhaber->name }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">unbesetzt</span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">TVöD-Bewertung</dt>
                                <dd class="mt-1 text-sm text-gray-700">
                                    @if($stelle->tvod_bewertung)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                            {{ $stelle->tvod_bewertung }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Wochenstunden</dt>
                                <dd class="mt-1 text-sm text-gray-700 flex items-center gap-2">
                                    @if($stelle->stunden)
                                        {{ number_format($stelle->stunden, 1, ',', '.') }} Std.
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $stelle->isVollzeit() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $stelle->isVollzeit() ? 'Vollzeit' : 'Teilzeit' }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Gesamtanteil</dt>
                                <dd class="mt-1">
                                    @php $gesamt = $stelle->gesamtanteil(); @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                        {{ $gesamt === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                                        {{ $gesamt }}%
                                        @if($gesamt !== 100) ⚠️ @endif
                                    </span>
                                </dd>
                            </div>

                        </dl>
                    </div>
                </div>

                {{-- Arbeitsvorgänge --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Arbeitsvorgänge</h3>
                        <span class="text-xs text-gray-500">{{ $stelle->arbeitsvorgaenge->count() }} Einträge</span>
                    </div>

                    @if($stelle->arbeitsvorgaenge->isEmpty())
                        <div class="px-6 py-8 text-center text-gray-400 text-sm">
                            Keine Arbeitsvorgänge definiert.
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach($stelle->arbeitsvorgaenge as $i => $av)
                            <div class="px-6 py-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1 min-w-0">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                            {{ $i + 1 }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ $av->betreff }}</h4>
                                            @if($av->beschreibung)
                                                <div class="mt-2 text-sm text-gray-600 prose prose-sm max-w-none">
                                                    {!! nl2br(e($av->beschreibung)) !!}
                                                </div>
                                            @else
                                                <p class="mt-1 text-xs text-gray-400 italic">Keine Beschreibung</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-sm font-bold
                                            {{ $av->anteil > 0 ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $av->anteil }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- Rechte Spalte: Aktionen (1/3) --}}
            <div class="space-y-4">

                <div class="bg-white shadow rounded-lg p-5 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Aktionen</h3>

                    @can('base.stellen.edit')
                    <a href="{{ route('stellen.edit', $stelle) }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Bearbeiten
                    </a>
                    @endcan

                    @can('base.stellen.delete')
                    <button @click="showDeleteModal = true"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-md hover:bg-red-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Löschen
                    </button>
                    @endcan

                    <a href="{{ route('stellen.index') }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition">
                        Zurück zur Liste
                    </a>
                </div>

                {{-- Meta-Info --}}
                <div class="bg-white shadow rounded-lg p-5 text-xs text-gray-400 space-y-1">
                    <div>Erstellt: {{ $stelle->created_at->format('d.m.Y') }}</div>
                    <div>Geändert: {{ $stelle->updated_at->format('d.m.Y H:i') }}</div>
                </div>

            </div>
        </div>

        {{-- Delete Modal --}}
        <div x-show="showDeleteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Stelle löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll die Stelle <strong>{{ $stelle->bezeichnung }}</strong> wirklich gelöscht werden?
                    Alle Arbeitsvorgänge werden ebenfalls gelöscht.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form action="{{ route('stellen.destroy', $stelle) }}" method="POST">
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
