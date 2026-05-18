<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.matrix') }}" class="text-gray-400 hover:text-gray-600">← Matrix</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">VZE-Bedarfsrechner</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kennzahlen --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">IST – Aktuell erbrachte Dienste</p>
                    <p class="text-5xl font-bold text-green-600">{{ number_format($istVzeGesamt, 2, ',', '.') }}</p>
                    <p class="text-sm text-gray-400 mt-1">VZE</p>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format($istStundenGesamt, 0, ',', '.') }} Jahresstunden</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">SOLL – Vollversorgung aller Schulen</p>
                    <p class="text-5xl font-bold text-indigo-600">{{ number_format($sollVzeGesamt, 2, ',', '.') }}</p>
                    <p class="text-sm text-gray-400 mt-1">VZE</p>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format($sollStundenGesamt, 0, ',', '.') }} Jahresstunden</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-xs text-blue-700">
                Grundlage: 1 VZE = 1.600 Netto-Jahresstunden (Tarifbeschäftigte Bayern, nach Urlaub & Feiertagen).
                IST = nur Dienste mit Status „Aktiv". SOLL = alle aktiven Dienstleistungen an allen Schulen.
            </div>

            {{-- Pro Schule --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">VZE-Bedarf pro Schule</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IST h/Jahr</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IST VZE</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">SOLL h/Jahr</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">SOLL VZE</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($schulenVze as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-medium text-gray-900">
                                        <a href="{{ route('schulen.show', $row['schule']) }}" class="hover:text-indigo-600">
                                            {{ $row['schule']->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $row['schule']->typFarbe() }}">
                                            {{ $row['schule']->typLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-600">
                                        {{ number_format($row['ist_stunden'], 1, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-green-700">
                                        {{ number_format($row['ist_vze'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-600">
                                        {{ number_format($row['soll_stunden'], 1, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-indigo-700">
                                        {{ number_format($row['soll_vze'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr class="font-semibold">
                                <td class="px-6 py-3 text-gray-700" colspan="2">Gesamt</td>
                                <td class="px-6 py-3 text-right text-gray-700">{{ number_format($istStundenGesamt, 1, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-green-700">{{ number_format($istVzeGesamt, 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-gray-700">{{ number_format($sollStundenGesamt, 1, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-indigo-700">{{ number_format($sollVzeGesamt, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Pro Dienstleistung --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">VZE-Bedarf pro Dienstleistung (IST)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dienstleistung</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktive Schulen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IST h/Jahr</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IST VZE</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($dienstStats as $row)
                                @if ($row['aktiv_count'] > 0)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 font-medium text-gray-900">
                                            <a href="{{ route('schulen.dienste.show', $row['dienst']) }}" class="hover:text-indigo-600">
                                                {{ $row['dienst']->name }}
                                            </a>
                                            @if ($row['dienst']->kategorie)
                                                <span class="ml-2 text-xs text-gray-400">{{ $row['dienst']->kategorie->name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-gray-600">{{ $row['aktiv_count'] }}</td>
                                        <td class="px-6 py-3 text-right text-gray-600">{{ number_format($row['ist_stunden'], 1, ',', '.') }}</td>
                                        <td class="px-6 py-3 text-right font-semibold text-green-700">{{ number_format($row['ist_vze'], 2, ',', '.') }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
