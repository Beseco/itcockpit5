<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibungen</h2>
                <a href="{{ route('stellenbeschreibungen.help') }}" title="Hilfe & Anleitung" class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></a>
            </div>
            @can('base.stellenbeschreibungen.edit')
            <a href="{{ route('stellenbeschreibungen.create') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">
                + Neue Stellenbeschreibung
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">Stellen</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-28">AV-Gesamt</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Bewertet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ergebnis</th>
                        <th class="px-4 py-3 w-32"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($stellenbeschreibungen as $sb)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $sb->bezeichnung }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $sb->stellen_count }}</td>
                            <td class="px-4 py-3 text-center">
                                @php $gesamt = $sb->gesamtanteil(); @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $gesamt === 100 ? 'bg-green-100 text-green-800' : ($gesamt === 0 ? 'bg-gray-100 text-gray-500' : 'bg-red-100 text-red-700') }}">
                                    {{ $gesamt }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">
                                {{ $sb->bewertet_am?->format('Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $sb->bewertungsergebnis ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right" x-data="{ showDelete: false }">
                                <div class="inline-flex items-center gap-1">
                                    @can('base.stellenbeschreibungen.edit')
                                    <a href="{{ route('stellenbeschreibungen.edit', $sb) }}"
                                       class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
                                       title="Bearbeiten">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button @click="showDelete = true" type="button"
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                            title="Löschen">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                         class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                        <div class="flex items-center justify-center min-h-screen px-4">
                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Stellenbeschreibung löschen</h3>
                                                <p class="text-sm text-gray-500 mb-4">Soll <strong>{{ $sb->bezeichnung }}</strong> wirklich gelöscht werden?</p>
                                                <div class="flex justify-end gap-3">
                                                    <button @click="showDelete = false" type="button"
                                                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Abbrechen</button>
                                                    <form method="POST" action="{{ route('stellenbeschreibungen.destroy', $sb) }}" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Löschen</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                Noch keine Stellenbeschreibungen vorhanden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
