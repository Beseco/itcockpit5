<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Rollen & Aufgaben</h2>
            @can('base.aufgaben.create')
                <a href="{{ route('aufgaben.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Aufgabe
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ deleteId: null, deleteName: '' }">

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('aufgaben.index') }}"
              class="mb-4 bg-white shadow rounded-lg p-4 flex flex-wrap items-end gap-3">

            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Aufgabe suchen…"
                       class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Gruppe</label>
                <select name="gruppe_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">Alle Gruppen</option>
                    @foreach($gruppen as $gruppe)
                        <option value="{{ $gruppe->id }}" @selected($gruppeId == $gruppe->id)>
                            {{ $gruppe->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Admin</label>
                <select name="admin_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">Alle Admins</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" @selected($adminId == $admin->id)>
                            {{ $admin->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 pb-0.5">
                <input type="checkbox" name="nur_eigene" id="nur_eigene" value="1"
                       @checked($nurEigene)
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="nur_eigene" class="text-sm text-gray-700 whitespace-nowrap">Nur eigene</label>
            </div>

            <div class="flex gap-2 pb-0.5">
                <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                    Filtern
                </button>
                @if($isFiltered)
                    <a href="{{ route('aufgaben.index') }}"
                       class="px-4 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                        Zurücksetzen
                    </a>
                @endif
            </div>
        </form>

        <div class="bg-white shadow rounded-lg overflow-hidden">

            @if($isFiltered)
                {{-- Flache Ergebnisliste --}}
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-500 flex items-center justify-between">
                    <span>{{ $aufgaben->count() }} Ergebnis(se)</span>
                    <div class="flex items-center gap-2">
                        <span>Sortierung:</span>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'asc']) }}"
                           class="px-2 py-0.5 rounded {{ $sortDir === 'asc' ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                            A → Z
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'desc']) }}"
                           class="px-2 py-0.5 rounded {{ $sortDir === 'desc' ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                            Z → A
                        </a>
                    </div>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Aufgabe</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Übergeordnet</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gruppe</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stellvertreter</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($aufgaben as $aufgabe)
                            <tr class="hover:bg-blue-50">
                                <td class="px-4 py-2 font-medium text-gray-800">
                                    <div class="flex items-center gap-2">
                                        {{ $aufgabe->name }}
                                        @if($aufgabe->beschreibung)
                                            <svg title="Hat Beschreibung" width="14" height="14" style="flex-shrink:0;color:#818cf8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-gray-500 text-xs">{{ $aufgabe->parent?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ $aufgabe->zuweisungen->pluck('gruppe.name')->filter()->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                    {{ $aufgabe->zuweisungen->pluck('admin.name')->filter()->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ $aufgabe->zuweisungen->pluck('stellvertreter.name')->filter()->implode(', ') ?: '—' }}
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        @can('base.aufgaben.create')
                                            <a href="{{ route('aufgaben.create', ['parent_id' => $aufgabe->id]) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-50 text-green-700 hover:bg-green-100 border border-green-200">+ Sub</a>
                                        @endcan
                                        <a href="{{ route('aufgaben.show', $aufgabe) }}"
                                           class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        @can('base.aufgaben.edit')
                                            <a href="{{ route('aufgaben.edit', $aufgabe) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        @endcan
                                        @can('base.aufgaben.delete')
                                            <button @click="deleteId = {{ $aufgabe->id }}; deleteName = '{{ addslashes($aufgabe->name) }}'"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Keine Aufgaben gefunden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                {{-- Baumansicht --}}
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Aufgabe</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gruppe</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Stellvertreter</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($aufgaben as $aufgabe)
                            @include('aufgaben._table_row', ['aufgabe' => $aufgabe, 'depth' => 0])
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Noch keine Aufgaben vorhanden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

        </div>

        {{-- Delete Modal --}}
        <div x-show="deleteId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aufgabe löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll die Aufgabe <strong x-text="deleteName"></strong> wirklich gelöscht werden?
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteId = null"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'{{ url('aufgaben') }}/' + deleteId" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm text-white bg-red-600 rounded-md hover:bg-red-700">
                            Löschen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
