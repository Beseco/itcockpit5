<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('stellenbeschreibungen.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibung: {{ $stellenbeschreibung->bezeichnung }}</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">{{ session('success') }}</div>
        @endif

        {{-- Bezeichnung bearbeiten --}}
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellenbeschreibungen.update', $stellenbeschreibung) }}" method="POST">
                @csrf @method('PUT')
                <div class="p-6 space-y-4">
                    @if ($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
                            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <div>
                        <x-input-label for="bezeichnung" value="Bezeichnung *" />
                        <x-text-input id="bezeichnung" name="bezeichnung" type="text" class="mt-1 block w-full"
                            value="{{ old('bezeichnung', $stellenbeschreibung->bezeichnung) }}" required />
                        <x-input-error :messages="$errors->get('bezeichnung')" class="mt-1" />
                    </div>
                </div>
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <a href="{{ route('stellenbeschreibungen.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Zurück</a>
                    <x-primary-button>Bezeichnung speichern</x-primary-button>
                </div>
            </form>
        </div>

        {{-- Arbeitsvorgänge --}}
        <div class="bg-white shadow rounded-lg p-6">
            @php $gesamt = $stellenbeschreibung->gesamtanteil(); @endphp
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-base font-semibold text-gray-800">Arbeitsvorgänge</h3>
                    <span class="text-sm font-semibold px-2 py-0.5 rounded
                        {{ $gesamt === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                        Gesamt: {{ $gesamt }}%
                        @if($gesamt !== 100) ⚠️ @endif
                    </span>
                </div>
                <a href="{{ route('sb.av.create', $stellenbeschreibung) }}"
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded">
                    + Hinzufügen
                </a>
            </div>

            <div class="border border-gray-200 rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Betreff</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">Anteil %</th>
                            <th class="px-3 py-2 w-36"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($stellenbeschreibung->arbeitsvorgaenge->values() as $i => $av)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500 font-medium">AV{{ $i + 1 }}</td>
                                <td class="px-3 py-3 text-gray-800">{{ $av->betreff }}</td>
                                <td class="px-3 py-3 text-center text-gray-700">{{ $av->anteil }}%</td>
                                <td class="px-3 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('sb.av.edit', [$stellenbeschreibung, $av]) }}"
                                           class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded px-2.5 py-1">
                                            Bearbeiten
                                        </a>
                                        <form method="POST" action="{{ route('sb.av.destroy', [$stellenbeschreibung, $av]) }}"
                                              onsubmit="return confirm('Arbeitsvorgang löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-xs text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 rounded px-2.5 py-1">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-400 text-sm">
                                    Noch keine Arbeitsvorgänge. Klicken Sie auf "+ Hinzufügen".
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Zugewiesene Stellen --}}
        @if($stellenbeschreibung->stellen->isNotEmpty())
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Zugewiesene Stellen ({{ $stellenbeschreibung->stellen->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($stellenbeschreibung->stellen as $stelle)
                    <a href="{{ route('stellen.edit', $stelle) }}"
                       class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-full">
                        {{ $stelle->stellennummer }}
                        @if($stelle->gruppe) <span class="ml-1 text-gray-400">– {{ $stelle->gruppe->name }}</span> @endif
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
