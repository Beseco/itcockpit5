<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Firmen & Dienstleister
        </h2>
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

                <a href="{{ route('dienstleister.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neuer Dienstleister
                </a>
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
                                            <div class="flex gap-0.5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span class="{{ $i <= $d->bewertung_gesamt ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                                                @endfor
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-sm">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap" x-data="{ showDelete: false }">
                                        <a href="{{ route('dienstleister.edit', $d) }}"
                                           class="text-indigo-600 hover:text-indigo-900 text-sm mr-3">Bearbeiten</a>
                                        <button @click="showDelete = true" type="button"
                                                class="text-red-600 hover:text-red-900 text-sm">Löschen</button>

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

            @if ($dienstleister->hasPages())
                <div class="mt-4">
                    {{ $dienstleister->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
