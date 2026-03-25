<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Fernwartung</h2>
            <div class="flex items-center gap-3">
                @can('fernwartung.tools.manage')
                <a href="{{ route('fernwartung.tools.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">Tools verwalten</a>
                @endcan
                @can('fernwartung.create')
                <a href="{{ route('fernwartung.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-gray-700 transition">
                    + Neue Fernwartung
                </a>
                @endcan
            </div>
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
            <form method="GET" action="{{ route('fernwartung.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Suche nach Name, Firma, Ziel, Tool …"
                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-primary-button type="submit">Suchen</x-primary-button>
                @if($search)
                    <a href="{{ route('fernwartung.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-600 hover:bg-gray-50">✕</a>
                @endif
            </form>

            {{-- Tabelle --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Datum / Zeit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Firma / Externer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Beobachter</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Ziel</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Tool</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Grund</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($eintraege as $fw)
                            <tr class="hover:bg-gray-50">
                                {{-- Datum / Beginn / Ende --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-gray-900 font-medium">{{ $fw->datum->format('d.m.Y') }}</div>
                                    <div class="text-gray-500 text-xs mt-0.5">
                                        {{ $fw->beginn }}
                                        @if($fw->ende)
                                            – {{ $fw->ende }}
                                        @else
                                            <form method="POST" action="{{ route('fernwartung.ende', $fw) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-1.5 py-0.5 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded hover:bg-amber-100 transition-colors">
                                                    Ende setzen
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                {{-- Firma / Externer --}}
                                <td class="px-4 py-3">
                                    <div class="text-gray-900 font-medium">{{ $fw->firma }}</div>
                                    <div class="text-gray-500 text-xs mt-0.5">{{ $fw->externer_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $fw->beobachter_label }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $fw->ziel }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $fw->tool }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-center">
                                    <span title="{{ $fw->grund }}"
                                          class="cursor-help inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors"
                                          style="font-size:0.75rem">?</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('fernwartung.edit')
                                        <a href="{{ route('fernwartung.edit', $fw) }}"
                                           class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                           title="Bearbeiten">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @endcan
                                        @if($fw->kannGeloeschtWerden() || $canDelete)
                                        <form method="POST" action="{{ route('fernwartung.destroy', $fw) }}"
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
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                                    Keine Einträge gefunden.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($eintraege->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $eintraege->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
