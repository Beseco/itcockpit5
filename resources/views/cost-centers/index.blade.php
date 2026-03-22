<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kostenstellen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-4">
                <span class="text-sm text-gray-500">{{ $costCenters->count() }} Einträge</span>
                <a href="{{ route('cost-centers.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Kostenstelle
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nummer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($costCenters as $cc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $cc->number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $cc->description ?: '–' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap" x-data="{ showDelete: false }">
                                    <a href="{{ route('cost-centers.edit', $cc) }}" class="text-indigo-600 hover:text-indigo-900 text-sm mr-3">Bearbeiten</a>
                                    <button @click="showDelete = true" type="button" class="text-red-600 hover:text-red-900 text-sm">Löschen</button>
                                    <div x-show="showDelete" x-cloak @click.away="showDelete = false" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                        <div class="flex items-center justify-center min-h-screen px-4">
                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Kostenstelle löschen</h3>
                                                <p class="text-sm text-gray-500 mb-4">Soll KST <strong>{{ $cc->number }}</strong> wirklich gelöscht werden?</p>
                                                <div class="flex justify-end gap-3">
                                                    <button @click="showDelete = false" type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Abbrechen</button>
                                                    <form action="{{ route('cost-centers.destroy', $cc) }}" method="POST" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Löschen</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Noch keine Kostenstellen vorhanden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
