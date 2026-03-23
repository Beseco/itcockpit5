<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Benutzerverwaltung</h2>
            @can('base.users.create')
                <a href="{{ route('users.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neuer Benutzer
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
        <form method="GET" action="{{ route('users.index') }}"
              class="mb-4 bg-white shadow rounded-lg p-4 flex flex-wrap items-end gap-3">

            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Name oder E-Mail…"
                       class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Gruppe</label>
                <select name="gruppe_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">Alle Gruppen</option>
                    @foreach($gruppen as $gruppe)
                        <option value="{{ $gruppe->id }}" @selected($gruppeId == $gruppe->id)>{{ $gruppe->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="active"
                        class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">Alle</option>
                    <option value="1" @selected(request('active') === '1')>Aktiv</option>
                    <option value="0" @selected(request('active') === '0')>Inaktiv</option>
                </select>
            </div>

            <div class="flex gap-2 pb-0.5">
                <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                    Filtern
                </button>
                @if($search || $gruppeId || request('active') !== null && request('active') !== '')
                    <a href="{{ route('users.index') }}"
                       class="px-4 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                        Zurücksetzen
                    </a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $sortDir === 'asc' ? 'desc' : 'asc']) }}"
                               class="inline-flex items-center gap-1 hover:text-gray-800">
                                Name
                                <span class="text-gray-400">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Gruppen</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Letzter Login</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    @if($user->avatarUrl())
                                        <img src="{{ $user->avatarUrl() }}" class="w-7 h-7 rounded-full object-cover" alt="">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    {{ $user->name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->gruppen as $gruppe)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                            {{ $gruppe->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @can('base.users.edit')
                                        {{-- Bearbeiten --}}
                                        <a href="{{ route('users.edit', $user) }}"
                                           title="Bearbeiten"
                                           class="p-1.5 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>

                                        {{-- Aktivieren / Deaktivieren --}}
                                        <form action="{{ route('users.toggle-active', $user) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    title="{{ $user->is_active ? 'Deaktivieren' : 'Aktivieren' }}"
                                                    class="p-1.5 rounded {{ $user->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : 'text-green-500 hover:text-green-700 hover:bg-green-50' }}">
                                                @if($user->is_active)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    @endcan

                                    @if(!session('impersonating_original_id') && $user->id !== auth()->id() && !$user->isSuperAdmin())
                                        @can('base.users.edit')
                                            {{-- Als Benutzer anmelden --}}
                                            <form action="{{ route('users.impersonate', $user) }}" method="POST">
                                                @csrf
                                                <button type="submit" title="Als diesen Benutzer anmelden"
                                                        class="p-1.5 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif

                                    @can('base.users.delete')
                                        @if($user->id !== auth()->id())
                                            {{-- Löschen --}}
                                            <button @click="deleteId = {{ $user->id }}; deleteName = '{{ addslashes($user->name) }}'"
                                                    title="Löschen"
                                                    class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Keine Benutzer gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($users->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">{{ $users->links() }}</div>
            @endif
        </div>

        {{-- Delete Modal --}}
        <div x-show="deleteId !== null" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Benutzer löschen</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Soll <strong x-text="deleteName"></strong> wirklich gelöscht werden? Diese Aktion kann nicht rückgängig gemacht werden.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deleteId = null; deleteName = ''"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <form :action="'{{ url('users') }}/' + deleteId" method="POST">
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
