<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stellenbeschreibungen</h2>
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
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    @can('base.stellenbeschreibungen.edit')
                                    <a href="{{ route('stellenbeschreibungen.edit', $sb) }}"
                                       class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Bearbeiten</a>
                                    <form method="POST" action="{{ route('stellenbeschreibungen.destroy', $sb) }}"
                                          onsubmit="return confirm('Stellenbeschreibung löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Löschen</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                Noch keine Stellenbeschreibungen vorhanden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
