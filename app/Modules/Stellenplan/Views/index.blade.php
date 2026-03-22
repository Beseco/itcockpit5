<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellenplan</h2>
            <span class="text-sm text-gray-500">
                @php $total = $gruppen->flatMap->stellen->count(); $frei = $gruppen->flatMap->stellen->filter->isFrei()->count(); @endphp
                {{ $total }} Stellen gesamt · <span class="text-amber-600 font-medium">{{ $frei }} FREI</span>
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
                                    <td class="px-3 py-2.5 text-right">
                                        <a href="{{ route('stellen.edit', $stelle) }}"
                                           class="text-gray-300 hover:text-indigo-600" title="Bearbeiten">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
