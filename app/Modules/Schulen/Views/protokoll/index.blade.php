<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.matrix') }}" class="text-gray-400 hover:text-gray-600">← Matrix</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schulen – Änderungsprotokoll</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form action="{{ route('schulen.protokoll') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Schule</label>
                        <select name="schule_id" onchange="this.form.submit()"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Schulen</option>
                            @foreach ($schulen as $schule)
                                <option value="{{ $schule->id }}" @selected($filterSchule == $schule->id)>{{ $schule->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Dienstleistung</label>
                        <select name="dienst_id" onchange="this.form.submit()"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Dienstleistungen</option>
                            @foreach ($dienste as $dienst)
                                <option value="{{ $dienst->id }}" @selected($filterDienst == $dienst->id)>{{ $dienst->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($filterSchule || $filterDienst)
                        <a href="{{ route('schulen.protokoll') }}"
                           class="text-xs text-indigo-600 hover:underline self-end pb-2">Filter zurücksetzen</a>
                    @endif
                </form>
            </div>

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitpunkt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dienstleistung</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vorher</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Nachher</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($eintraege as $eintrag)
                            @php
                                $p      = $eintrag->payload ?? [];
                                $alt    = $p['alt'] ?? $p['status'] ?? '—';
                                $neu    = $p['neu'] ?? $p['status'] ?? '—';
                                $labels = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_LABELS;
                                $colors = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_COLORS;
                                $icons  = \App\Modules\Schulen\Models\SchuleDienstleistung::STATUS_ICONS;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-500 whitespace-nowrap">
                                    <span title="{{ $eintrag->created_at->format('d.m.Y H:i:s') }}">
                                        {{ $eintrag->created_at->format('d.m.Y H:i') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-700 whitespace-nowrap">
                                    {{ $eintrag->user?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-gray-800">
                                    @if (isset($p['schule_id']))
                                        <a href="{{ route('schulen.show', $p['schule_id']) }}"
                                           class="hover:text-indigo-600">{{ $p['schule'] ?? $p['schule_id'] }}</a>
                                    @else
                                        {{ $p['schule'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-800">
                                    @if (isset($p['dienst_id']))
                                        <a href="{{ route('schulen.dienste.show', $p['dienst_id']) }}"
                                           class="hover:text-indigo-600">{{ $p['dienst'] ?? $p['dienst_id'] }}</a>
                                    @else
                                        {{ $p['dienst'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @if (isset($colors[$alt]))
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $colors[$alt] }}">
                                            {{ $icons[$alt] }} {{ $labels[$alt] }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ $alt }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @if (isset($colors[$neu]))
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $colors[$neu] }}">
                                            {{ $icons[$neu] }} {{ $labels[$neu] }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ $neu }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Keine Einträge gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($eintraege->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $eintraege->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
