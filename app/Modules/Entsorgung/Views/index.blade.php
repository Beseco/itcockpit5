<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Entsorgung</h2>
            @can('entsorgung.edit')
            <div class="flex items-center gap-2">
                <a href="{{ route('entsorgung.listen.hersteller') }}"
                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded-md hover:bg-gray-50 transition">
                    Hersteller
                </a>
                <a href="{{ route('entsorgung.listen.typen') }}"
                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded-md hover:bg-gray-50 transition">
                    Gerätetypen
                </a>
                <a href="{{ route('entsorgung.listen.gruende') }}"
                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded-md hover:bg-gray-50 transition">
                    Entsorgungsgründe
                </a>
                <a href="{{ route('entsorgung.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-gray-700 transition">
                    + Neuer Eintrag
                </a>
            </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Suche --}}
            <form method="GET" action="{{ route('entsorgung.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Suche nach Gerät, Hersteller, Inventar, Entsorger, Entsorgungsgrund …"
                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-primary-button type="submit">Suchen</x-primary-button>
                @if($search)
                    <a href="{{ route('entsorgung.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-600 hover:bg-gray-50">✕</a>
                @endif
            </form>

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Datum</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Gerät</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Inventar-Nr.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Typ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Bisheriger Nutzer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Entsorgungsgrund</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Entsorger</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Grundschutz</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($eintraege as $eintrag)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                    {{ $eintrag->datum->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 font-medium">{{ $eintrag->name }}</div>
                                    <div class="text-gray-500 text-xs mt-0.5">{{ $eintrag->hersteller_name }} – {{ $eintrag->modell }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap font-mono">
                                    {{ str_pad($eintrag->inventar, 10, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $eintrag->typ ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $eintrag->nutzer_name }}</td>
                                <td class="px-4 py-3 text-gray-700 text-xs max-w-[200px]">
                                    {{ $eintrag->entsorgungsgrund ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $eintrag->entsorger }}</td>
                                <td class="px-4 py-3">
                                    @if($eintrag->grundschutz)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ja</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                                              title="{{ $eintrag->grundschutzgrund }}">Nein ⚠</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if($canEdit)
                                        <a href="{{ route('entsorgung.edit', $eintrag) }}"
                                           class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                           title="Bearbeiten">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @endif
                                        @if($eintrag->kannGeloeschtWerden() || $canDelete)
                                        <form method="POST" action="{{ route('entsorgung.destroy', $eintrag) }}"
                                              onsubmit="return confirm('Eintrag wirklich löschen?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                    title="Löschen">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400 text-sm">
                                    Keine Einträge gefunden.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <x-per-page-select :per-page="$perPage" />
                    @if($eintraege->hasPages())
                        <div>{{ $eintraege->links() }}</div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
