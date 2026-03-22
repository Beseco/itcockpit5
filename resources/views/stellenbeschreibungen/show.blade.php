<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibung: {{ $stellenbeschreibung->bezeichnung }}</h2>
        </div>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Arbeitsvorgänge --}}
        <div class="bg-white shadow rounded-lg p-6">
            @php $gesamt = $stellenbeschreibung->gesamtanteil(); @endphp
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800">Arbeitsvorgänge</h3>
                <span class="text-sm font-semibold px-2 py-0.5 rounded
                    {{ $gesamt === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">
                    Gesamt: {{ $gesamt }}%
                </span>
            </div>

            <div class="border border-gray-200 rounded-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Betreff</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">Anteil %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($stellenbeschreibung->arbeitsvorgaenge->values() as $i => $av)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500 font-medium">AV{{ $i + 1 }}</td>
                                <td class="px-3 py-3 text-gray-800 font-medium">{{ $av->betreff }}</td>
                                <td class="px-3 py-3 text-gray-600 text-xs">
                                    @if($av->beschreibung)
                                        {{ Str::limit($av->beschreibung, 120) }}
                                    @else
                                        <span class="text-gray-400 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center text-gray-700 font-medium">{{ $av->anteil }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-400 text-sm">
                                    Noch keine Arbeitsvorgänge definiert.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
