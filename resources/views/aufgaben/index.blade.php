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

        <div class="bg-white shadow rounded-lg overflow-hidden">
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
                    <form :action="'/aufgaben/' + deleteId" method="POST">
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
