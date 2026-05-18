<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.dienste.index') }}" class="text-gray-400 hover:text-gray-600">← Dienstleistungen</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $dienstleistung->name }}</h2>
            @if (!$dienstleistung->is_active)
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-500">Inaktiv</span>
            @endif
            @can('schulen.edit')
                <a href="{{ route('schulen.dienste.edit', $dienstleistung) }}"
                   class="ml-auto inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Bearbeiten
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Details --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Details</h3>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-500">Kategorie:</span>
                        <span class="ml-2 text-gray-900">{{ $dienstleistung->kategorie?->name ?? '—' }}</span>
                    </div>
                    @if ($dienstleistung->beschreibung)
                        <div>
                            <span class="font-medium text-gray-500">Beschreibung:</span>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $dienstleistung->beschreibung }}</p>
                        </div>
                    @endif
                    @if ($dienstleistung->dokumentation_url)
                        <div>
                            <span class="font-medium text-gray-500">Dokumentation:</span>
                            <a href="{{ $dienstleistung->dokumentation_url }}" target="_blank" rel="noopener"
                               class="ml-2 text-indigo-600 hover:underline break-all">
                                📖 Confluence öffnen →
                            </a>
                        </div>
                    @endif
                    <div>
                        <span class="font-medium text-gray-500">Stundenbedarf:</span>
                        @if ($dienstleistung->stunden_wert !== null)
                            <span class="ml-2 text-gray-900">
                                {{ number_format($dienstleistung->stunden_wert, 1, ',', '.') }}
                                {{ $dienstleistung->stunden_modus === 'wochenstunden' ? 'h/Woche' : 'h/Jahr' }}
                                @if ($dienstleistung->stunden_modus === 'wochenstunden')
                                    → {{ number_format($dienstleistung->jahresstunden(), 1, ',', '.') }} h/Jahr
                                @endif
                            </span>
                        @else
                            <span class="ml-2 text-gray-400">—</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- VZE-Berechnung --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">VZE-Bedarf</h3>
                    <p class="text-xs text-gray-400 mt-0.5">1 VZE = {{ number_format(\App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN, 0, ',', '.') }} Netto-Jahresstunden</p>
                </div>

                @if ($vze['pro_schule'] === null)
                    <div class="p-6 text-sm text-gray-400">Kein Stundenbedarf hinterlegt – VZE-Berechnung nicht möglich.</div>
                @else
                    {{-- Kennzahlen-Kacheln --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                        <div class="px-6 py-5 text-center">
                            <p class="text-xs text-gray-500 mb-1">Pro Schule</p>
                            <p class="text-2xl font-bold text-indigo-700">{{ number_format($vze['pro_schule'], 3, ',', '.') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">VZE</p>
                        </div>
                        <div class="px-6 py-5 text-center {{ $schulenAktiv > 0 ? 'bg-green-50' : '' }}">
                            <p class="text-xs text-gray-500 mb-1">
                                Aktuell aktiv
                                <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ $schulenAktiv }} / {{ $schulenGesamt }}</span>
                            </p>
                            <p class="text-2xl font-bold {{ $schulenAktiv > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                {{ number_format($vze['ist_gesamt'], 3, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">VZE gesamt · {{ number_format($vze['ist_stunden'], 1, ',', '.') }} h/Jahr</p>
                        </div>
                        <div class="px-6 py-5 text-center bg-indigo-50">
                            <p class="text-xs text-gray-500 mb-1">
                                Wenn alle Schulen
                                <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">{{ $schulenGesamt }}</span>
                            </p>
                            <p class="text-2xl font-bold text-indigo-700">{{ number_format($vze['alle_schulen'], 3, ',', '.') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">VZE gesamt · {{ number_format($schulenGesamt * ($dienstleistung->jahresstunden() ?? 0), 1, ',', '.') }} h/Jahr</p>
                        </div>
                    </div>

                    {{-- Skalierungstabelle --}}
                    <div class="px-6 pb-6 pt-2">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Skalierung nach Schulanzahl</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left pb-2 text-xs font-medium text-gray-400">Schulen</th>
                                        <th class="text-right pb-2 text-xs font-medium text-gray-400">Stunden/Jahr</th>
                                        <th class="text-right pb-2 text-xs font-medium text-gray-400">VZE</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @php
                                        $h = $dienstleistung->jahresstunden() ?? 0;
                                        $vzeBase = \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN;
                                        $steps = array_unique(array_filter([
                                            1,
                                            $schulenAktiv > 0 && $schulenAktiv !== 1 && $schulenAktiv !== $schulenGesamt ? $schulenAktiv : null,
                                            $schulenGesamt,
                                        ]));
                                        sort($steps);
                                    @endphp
                                    @foreach ($steps as $n)
                                        <tr class="{{ $n === $schulenAktiv ? 'bg-green-50 font-semibold' : ($n === $schulenGesamt ? 'bg-indigo-50' : '') }}">
                                            <td class="py-2 text-gray-700">
                                                {{ $n }}
                                                @if ($n === $schulenAktiv && $n === $schulenGesamt)
                                                    <span class="ml-1 text-xs text-green-600">(alle aktiv)</span>
                                                @elseif ($n === $schulenAktiv)
                                                    <span class="ml-1 text-xs text-green-600">(aktuell aktiv)</span>
                                                @elseif ($n === $schulenGesamt)
                                                    <span class="ml-1 text-xs text-indigo-500">(alle Schulen)</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right text-gray-600">{{ number_format($n * $h, 1, ',', '.') }} h</td>
                                            <td class="py-2 text-right {{ $n === $schulenAktiv ? 'text-green-700' : 'text-indigo-700' }}">
                                                {{ number_format($n * $h / $vzeBase, 3, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">
                            * Skalierungswerte nutzen den Standardstundensatz. Schulen mit individuell überschriebenem Stundenwert können abweichen.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Schulen die den Dienst nutzen --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                        Schulen mit aktivem Einsatz
                        @if ($schulenAktiv > 0)
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ $schulenAktiv }}</span>
                        @endif
                    </h3>
                    <a href="{{ route('schulen.protokoll', ['dienst_id' => $dienstleistung->id]) }}"
                       class="text-xs text-gray-500 hover:text-indigo-600">Protokoll →</a>
                </div>
                @if ($aktivePivots->isEmpty())
                    <div class="px-6 py-5 text-sm text-gray-400">Keine Schule nutzt diesen Dienst aktuell aktiv.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stunden/Jahr</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">VZE</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notiz</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($aktivePivots as $schule)
                                @php
                                    $h = $schule->pivot->stunden_override ?? $dienstleistung->jahresstunden() ?? 0;
                                    $vzeSchule = round($h / \App\Modules\Schulen\Models\Dienstleistung::VZE_JAHRESSTUNDEN, 3);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-800">
                                        <a href="{{ route('schulen.show', $schule) }}" class="hover:text-indigo-600">
                                            {{ $schule->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $schule->typFarbe() }}">
                                            {{ $schule->typLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-600">
                                        {{ number_format($h, 1, ',', '.') }} h
                                        @if ($schule->pivot->stunden_override !== null)
                                            <span class="text-xs text-orange-500 ml-1">(override)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-green-700">
                                        {{ number_format($vzeSchule, 3, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-400 text-xs italic">
                                        {{ $schule->pivot->notizen ?? '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
