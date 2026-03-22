<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('stellen.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Stelle: {{ $stelle->stellennummer }}</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{ showDeleteModal: false }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Stammdaten --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Stellendaten</h3>
                @can('base.stellen.edit')
                    <a href="{{ route('stellen.edit', $stelle) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded px-2.5 py-1">
                        Bearbeiten
                    </a>
                @endcan
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stellennummer</dt>
                        <dd class="mt-1 text-lg font-bold font-mono text-gray-900">{{ $stelle->stellennummer }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stellenbeschreibung</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            @if($stelle->stellenbeschreibung)
                                <a href="{{ route('stellenbeschreibungen.edit', $stelle->stellenbeschreibung) }}"
                                   class="text-indigo-600 hover:underline">
                                    {{ $stelle->stellenbeschreibung->bezeichnung }} ↗
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

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
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">FREI</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">HH-Bewertung (Soll)</dt>
                        <dd class="mt-1 text-sm text-gray-700">
                            @if($stelle->haushalt_bewertung)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                    {{ $stelle->haushalt_bewertung }}
                                </span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bes.-Gruppe (Ist)</dt>
                        <dd class="mt-1 text-sm text-gray-700">
                            @if($stelle->bes_gruppe)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ $stelle->bes_gruppe }}
                                </span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Belegung %</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $stelle->belegung !== null ? number_format($stelle->belegung, 2, ',', '.').' %' : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Gesamtarbeitszeit %</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $stelle->gesamtarbeitszeit !== null ? number_format($stelle->gesamtarbeitszeit, 2, ',', '.').' %' : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Anteil Stelle %</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $stelle->anteil_stelle !== null ? number_format($stelle->anteil_stelle, 2, ',', '.').' %' : '—' }}</dd>
                    </div>

                </dl>
            </div>
        </div>

        {{-- Stellenbeschreibung / Arbeitsvorgänge --}}
        @if($stelle->stellenbeschreibung)
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Arbeitsvorgänge</h3>
                    <p class="text-xs text-gray-500 mt-0.5">aus Stellenbeschreibung: {{ $stelle->stellenbeschreibung->bezeichnung }}</p>
                </div>
                <a href="{{ route('stellenbeschreibungen.edit', $stelle->stellenbeschreibung) }}"
                   class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded px-2.5 py-1">
                    Stellenbeschreibung bearbeiten ↗
                </a>
            </div>
            @php $gesamt = $stelle->stellenbeschreibung->gesamtanteil(); @endphp
            <p class="text-sm text-gray-500 mb-4">
                {{ $stelle->stellenbeschreibung->arbeitsvorgaenge->count() }} Arbeitsvorgang/-vorgänge –
                <span class="{{ $gesamt === 100 ? 'text-green-600' : 'text-red-600' }} font-medium">{{ $gesamt }}% Gesamt</span>
            </p>
            @if($stelle->stellenbeschreibung->arbeitsvorgaenge->isNotEmpty())
            <div class="border border-gray-200 rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Betreff</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">Anteil %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($stelle->stellenbeschreibung->arbeitsvorgaenge as $i => $av)
                            <tr>
                                <td class="px-3 py-3 text-gray-500 font-medium">AV{{ $i + 1 }}</td>
                                <td class="px-3 py-3 text-gray-800">{{ $av->betreff }}</td>
                                <td class="px-3 py-3 text-center text-gray-700">{{ $av->anteil }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        {{-- Meta & Aktionen --}}
        <div class="flex items-center justify-between text-xs text-gray-400">
            <div>Erstellt: {{ $stelle->created_at->format('d.m.Y') }} · Geändert: {{ $stelle->updated_at->format('d.m.Y H:i') }}</div>
            @can('base.stellen.edit')
            <button @click="showDeleteModal = true"
                    class="text-xs text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 rounded px-2.5 py-1">
                Stelle löschen
            </button>
            @endcan
        </div>

        {{-- Delete Modal --}}
        <div x-show="showDeleteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Stelle löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll die Stelle <strong>{{ $stelle->stellennummer }}</strong> wirklich gelöscht werden?
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
