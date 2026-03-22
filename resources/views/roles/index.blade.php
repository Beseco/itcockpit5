<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rollenverwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500">{{ $roles->count() }} Rollen</p>
                @can('base.roles.create')
                <a href="{{ route('roles.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Rolle
                </a>
                @endcan
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rollenname</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Berechtigungen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($roles as $role)
                                <tr class="hover:bg-gray-50" x-data="{ showDelete: false }">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ $role->name }}</span>
                                        @if ($role->name === 'Superadministrator')
                                            <span class="ml-2 px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">System</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if ($role->name === 'Superadministrator')
                                            <span class="italic text-gray-400">Alle (automatisch)</span>
                                        @else
                                            {{ $role->permissions_count }} Berechtigungen
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $role->users_count }} Benutzer
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        @can('base.roles.edit')
                                        <a href="{{ route('roles.edit', $role) }}"
                                           class="text-indigo-600 hover:text-indigo-900">Bearbeiten</a>
                                        @endcan

                                        @can('base.roles.delete')
                                        @if ($role->name !== 'Superadministrator')
                                        <button @click="showDelete = true" type="button"
                                                class="text-red-600 hover:text-red-900">Löschen</button>

                                        <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                             class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                            <div class="flex items-center justify-center min-h-screen px-4">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Rolle löschen</h3>
                                                    <p class="text-sm text-gray-500 mb-4">
                                                        Soll die Rolle „{{ $role->name }}" wirklich gelöscht werden?
                                                        @if ($role->users_count > 0)
                                                            <span class="block mt-2 text-red-600 font-medium">
                                                                Achtung: {{ $role->users_count }} Benutzer haben diese Rolle zugewiesen.
                                                            </span>
                                                        @endif
                                                    </p>
                                                    <div class="flex justify-end gap-3">
                                                        <button @click="showDelete = false" type="button"
                                                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                            Abbrechen
                                                        </button>
                                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline">
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
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
