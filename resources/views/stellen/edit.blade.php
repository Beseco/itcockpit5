<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Stelle bearbeiten: {{ $stelle->bezeichnung }}</h2>
    </x-slot>
    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Stammdaten-Formular --}}
        <div class="bg-white shadow rounded-lg">
            <form action="{{ route('stellen.update', $stelle) }}" method="POST">
                @csrf @method('PUT')
                @include('stellen._form')
            </form>
        </div>

        {{-- Arbeitsvorgänge (Read-only Liste) --}}
        <div class="bg-white shadow rounded-lg p-6">
            @php $totalAnteil = $stelle->arbeitsvorgaenge->sum('anteil'); @endphp

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-base font-semibold text-gray-800">Stellenbeschreibung (Arbeitsvorgänge)</h3>
                    <span class="text-sm font-semibold px-2 py-0.5 rounded
                        {{ $totalAnteil === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                        Gesamt: {{ $totalAnteil }}%
                        @if($totalAnteil !== 100) ⚠️ @endif
                    </span>
                </div>
                <a href="{{ route('stellen.av.create', $stelle) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded">
                    + Hinzufügen
                </a>
            </div>

            <div class="border border-gray-200 rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Betreff</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24 text-center">Anteil %</th>
                            <th class="px-3 py-2 w-36"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($stelle->arbeitsvorgaenge->sortBy('sort_order')->values() as $i => $av)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500 font-medium">AV{{ $i + 1 }}</td>
                                <td class="px-3 py-3 text-gray-800">{{ $av->betreff }}</td>
                                <td class="px-3 py-3 text-gray-700 text-center">{{ $av->anteil }}%</td>
                                <td class="px-3 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('stellen.av.edit', [$stelle, $av]) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded">
                                            Bearbeiten
                                        </a>
                                        <form method="POST" action="{{ route('stellen.av.destroy', [$stelle, $av]) }}"
                                              onsubmit="return confirm('Arbeitsvorgang löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 rounded">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-400 text-sm">
                                    Noch keine Arbeitsvorgänge vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
