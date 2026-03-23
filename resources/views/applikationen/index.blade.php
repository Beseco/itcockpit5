<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applikationen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Suche + Neu-Button --}}
            <div class="flex items-center justify-between mb-4 gap-4">
                <form action="{{ route('applikationen.index') }}" method="GET" class="flex gap-2">
                    <x-text-input name="search" type="text" placeholder="Name, Zweck, SG, Hersteller..."
                                  value="{{ $search }}" class="w-72" />
                    @if ($sort !== 'name') <input type="hidden" name="sort" value="{{ $sort }}"> @endif
                    @if ($order !== 'ASC') <input type="hidden" name="order" value="{{ $order }}"> @endif
                    <x-primary-button type="submit">Suchen</x-primary-button>
                    @if ($search)
                        <a href="{{ route('applikationen.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            Zurücksetzen
                        </a>
                    @endif
                </form>
                @can('applikationen.create')
                <a href="{{ route('applikationen.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Neue Applikation
                </a>
                @endcan
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            @php $nextOrder = $order === 'ASC' ? 'DESC' : 'ASC'; @endphp
                            <tr>
                                @foreach ([
                                    'name'             => 'Name / Hersteller',
                                    'baustein'         => 'Baustein',
                                    'sg'               => 'Sachgebiet',
                                    'verantwortlich_sg'=> 'Verantwortlichkeiten',
                                ] as $col => $label)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ route('applikationen.index', ['sort' => $col, 'order' => $sort === $col ? $nextOrder : 'ASC', 'search' => $search]) }}"
                                       class="hover:text-gray-800 flex items-center gap-1">
                                        {{ $label }}
                                        @if($sort === $col) <span>{{ $order === 'ASC' ? '↑' : '↓' }}</span> @endif
                                    </a>
                                </th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schutzbedarf</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($apps as $app)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $app->name }}</div>
                                        @if ($app->hersteller)
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $app->hersteller }}</div>
                                        @endif
                                        @if ($app->einsatzzweck)
                                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($app->einsatzzweck, 60) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($app->baustein)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">
                                                {{ $app->baustein }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->sg ?: '–' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if ($app->verantwortlich_sg)
                                            <div>{{ $app->verantwortlich_sg }}</div>
                                        @endif
                                        @if ($app->adminUser)
                                            <div class="text-xs text-gray-400">Admin: {{ $app->adminUser->name }}</div>
                                        @elseif ($app->admin)
                                            <div class="text-xs text-gray-400">Admin: {{ $app->admin }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-1">
                                            @foreach (['confidentiality' => 'V', 'integrity' => 'I', 'availability' => 'V'] as $field => $prefix)
                                                @php $farbe = \App\Models\Applikation::SCHUTZBEDARF_FARBEN[$app->$field] ?? 'bg-gray-100 text-gray-700'; @endphp
                                                <span class="px-1.5 py-0.5 text-xs font-bold rounded {{ $farbe }}"
                                                      title="{{ ['confidentiality'=>'Vertraulichkeit','integrity'=>'Integrität','availability'=>'Verfügbarkeit'][$field] }}: {{ \App\Models\Applikation::SCHUTZBEDARF[$app->$field] ?? $app->$field }}">
                                                    {{ $app->$field }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium" x-data="{ showDelete: false }">
                                        @can('applikationen.edit')
                                        <a href="{{ route('applikationen.edit', $app) }}"
                                           class="text-indigo-600 hover:text-indigo-900 mr-3">Bearbeiten</a>
                                        @endcan
                                        @can('applikationen.delete')
                                        <button @click="showDelete = true" type="button"
                                                class="text-red-600 hover:text-red-900">Löschen</button>

                                        <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                             class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                            <div class="flex items-center justify-center min-h-screen px-4">
                                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Applikation löschen</h3>
                                                    <p class="text-sm text-gray-500 mb-4">Soll <strong>{{ $app->name }}</strong> wirklich gelöscht werden?</p>
                                                    <div class="flex justify-end gap-3">
                                                        <button @click="showDelete = false" type="button"
                                                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Abbrechen</button>
                                                        <form action="{{ route('applikationen.destroy', $app) }}" method="POST" class="inline">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Löschen</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">Keine Applikationen gefunden.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($apps->hasPages())
                <div class="mt-4">{{ $apps->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
