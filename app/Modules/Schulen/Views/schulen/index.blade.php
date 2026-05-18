<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('schulen.matrix') }}" class="text-gray-400 hover:text-gray-600">← Matrix</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schulen</h2>
            @can('schulen.edit')
                <a href="{{ route('schulen.create') }}"
                   class="ml-auto inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    + Schule anlegen
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white shadow-sm sm:rounded-lg mb-4 p-4">
                <form id="schule-filter" action="{{ route('schulen.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Name, Ort…"
                               class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Schultyp</label>
                        <select name="filter_typ" onchange="document.getElementById('schule-filter').submit()"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Typen</option>
                            @foreach ($schulTypen as $typ)
                                <option value="{{ $typ->id }}" @selected($filterTyp == $typ->id)>{{ $typ->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Suchen
                    </button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ort</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aktive Dienste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($schulen as $schule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    <a href="{{ route('schulen.show', $schule) }}" class="hover:text-indigo-600">
                                        {{ $schule->name }}
                                    </a>
                                    @if ($schule->kurzname)
                                        <span class="ml-1 text-xs text-gray-400">({{ $schule->kurzname }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $schule->typFarbe() }}">
                                        {{ $schule->typLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-600">{{ $schule->ort ?? '—' }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if ($schule->aktive_dienste_count > 0)
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            {{ $schule->aktive_dienste_count }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('schulen.show', $schule) }}"
                                       class="text-indigo-600 hover:text-indigo-800 text-xs font-medium mr-3">Detail</a>
                                    @can('schulen.edit')
                                        <a href="{{ route('schulen.edit', $schule) }}"
                                           class="text-gray-600 hover:text-gray-800 text-xs font-medium mr-3">Bearbeiten</a>
                                    @endcan
                                    @can('schulen.delete')
                                        <form action="{{ route('schulen.destroy', $schule) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Schule {{ addslashes($schule->name) }} wirklich löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                                Löschen
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Keine Schulen gefunden.
                                    @can('schulen.edit')
                                        <a href="{{ route('schulen.create') }}" class="text-indigo-600 hover:underline ml-1">Jetzt anlegen →</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($schulen->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $schulen->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
