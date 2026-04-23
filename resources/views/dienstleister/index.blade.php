<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Firmen & Dienstleister</h2>
            <a href="{{ route('dienstleister.help') }}" title="Hilfe & Anleitung" class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Suche & Neu-Button --}}
            <div class="flex items-center justify-between mb-4 gap-4">
                <form action="{{ route('dienstleister.index') }}" method="GET" class="flex gap-2">
                    <x-text-input name="search" type="text" placeholder="Firma, Ort, Typ, Fachgebiet..."
                                  value="{{ $search }}" class="w-72" />
                    @if ($sort !== 'firmenname') <input type="hidden" name="sort" value="{{ $sort }}"> @endif
                    @if ($order !== 'ASC') <input type="hidden" name="order" value="{{ $order }}"> @endif
                    <x-primary-button type="submit">Suchen</x-primary-button>
                    @if ($search)
                        <a href="{{ route('dienstleister.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Zurücksetzen
                        </a>
                    @endif
                </form>

                @can('dienstleister.create')
                <a href="{{ route('dienstleister.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neuer Dienstleister
                </a>
                @endcan
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @php
                                    $nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
                                @endphp
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('dienstleister.index', ['sort' => 'firmenname', 'order' => $sort === 'firmenname' ? $nextOrder : 'ASC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        Firma
                                        @if ($sort === 'firmenname')
                                            <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('dienstleister.index', ['sort' => 'dienstleister_typ', 'order' => $sort === 'dienstleister_typ' ? $nextOrder : 'ASC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        Typ / Fachgebiet
                                        @if ($sort === 'dienstleister_typ')
                                            <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('dienstleister.index', ['sort' => 'ort', 'order' => $sort === 'ort' ? $nextOrder : 'ASC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        Ort
                                        @if ($sort === 'ort')
                                            <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('dienstleister.index', ['sort' => 'status', 'order' => $sort === 'status' ? $nextOrder : 'ASC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        Status
                                        @if ($sort === 'status')
                                            <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('dienstleister.index', ['sort' => 'bewertung_gesamt', 'order' => $sort === 'bewertung_gesamt' ? $nextOrder : 'DESC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        Bewertung
                                        @if ($sort === 'bewertung_gesamt')
                                            <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($dienstleister as $d)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900">{{ $d->firmenname }}</span>
                                            @if ($d->kritischer_dienstleister)
                                                <span class="px-1.5 py-0.5 text-xs font-bold bg-red-100 text-red-700 rounded" title="Kritischer Dienstleister">!</span>
                                            @endif
                                            @if ($d->empfehlung)
                                                <span class="text-green-500" title="Empfohlen">👍</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $d->dienstleister_typ ?: '–' }}
                                        @if ($d->fachgebiet)
                                            <br><span class="text-xs text-gray-400">{{ Str::limit($d->fachgebiet, 30) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ trim(($d->plz ?? '') . ' ' . ($d->ort ?? '')) ?: '–' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2 text-gray-400">
                                            @if ($d->telefon)
                                                <span title="{{ $d->telefon }}">📞</span>
                                            @endif
                                            @if ($d->email)
                                                <a href="mailto:{{ $d->email }}" class="hover:text-indigo-600" title="{{ $d->email }}">✉️</a>
                                            @endif
                                            @if ($d->website)
                                                <a href="{{ $d->website }}" target="_blank" class="hover:text-indigo-600" title="{{ $d->website }}">🌐</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $d->status === 'aktiv' ? 'bg-green-100 text-green-800' :
                                                   ($d->status === 'gesperrt' ? 'bg-red-100 text-red-800' :
                                                   ($d->status === 'potenziell' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')) }}">
                                                {{ \App\Models\Dienstleister::STATUS[$d->status] ?? $d->status }}
                                            </span>
                                            @if ($d->verarbeitet_personenbezogene_daten)
                                                @if ($d->av_vertrag_vorhanden)
                                                    <span class="px-2 text-xs rounded-full bg-indigo-100 text-indigo-700 w-fit">AV-Vertrag ✓</span>
                                                @else
                                                    <span class="px-2 text-xs rounded-full bg-yellow-100 text-yellow-700 w-fit">AV fehlt!</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($d->bewertung_gesamt)
                                            <div class="flex gap-0.5 text-base leading-none">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if($i <= $d->bewertung_gesamt)
                                                        <span class="text-yellow-400">★</span>
                                                    @else
                                                        <span class="text-gray-300">☆</span>
                                                    @endif
                                                @endfor
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-sm">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap" x-data="{ showDelete: false }">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('dienstleister.show', $d) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200"
                                               title="Ansehen">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            @can('dienstleister.edit')
                                            <a href="{{ route('dienstleister.edit', $d) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
                                               title="Bearbeiten">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @endcan
                                            @can('dienstleister.delete')
                                            <button @click="showDelete = true" type="button"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                                    title="Löschen">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>

                                            {{-- Lösch-Modal --}}
                                            <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                                 class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                                <div class="flex items-center justify-center min-h-screen px-4">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Dienstleister löschen</h3>
                                                        <p class="text-sm text-gray-500 mb-4">
                                                            Soll <strong>{{ $d->firmenname }}</strong> wirklich gelöscht werden?
                                                        </p>
                                                        <div class="flex justify-end gap-3">
                                                            <button @click="showDelete = false" type="button"
                                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                                Abbrechen
                                                            </button>
                                                            <form action="{{ route('dienstleister.destroy', $d) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                                    Löschen
                                                                </button>
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
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        Keine Einträge vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between flex-wrap gap-2">
                <x-per-page-select :per-page="$perPage" />
                @if ($dienstleister->hasPages())
                    <div>{{ $dienstleister->links() }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
