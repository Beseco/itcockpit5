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

            @php
                $anyFilterActive = $filterAbteilungId || $filterBaustein || $filterAdminUserId
                    || $filterOhneVerantwortlich || $filterConfidentiality || $filterIntegrity
                    || $filterAvailability || $filterOffeneRevision;
                $activeFilterCount = (int)(bool)$filterAbteilungId + (int)(bool)$filterBaustein
                    + (int)(bool)$filterAdminUserId + (int)$filterOhneVerantwortlich
                    + (int)(bool)$filterConfidentiality + (int)(bool)$filterIntegrity
                    + (int)(bool)$filterAvailability + (int)$filterOffeneRevision;
            @endphp

            {{-- Einheitliches Such- & Filterformular --}}
            <form action="{{ route('applikationen.index') }}" method="GET"
                  id="filter-form"
                  x-data="{
                      filtersOpen: {{ $anyFilterActive ? 'true' : 'false' }},
                      searchTimer: null,
                      debouncedSearch() {
                          clearTimeout(this.searchTimer);
                          this.searchTimer = setTimeout(() => document.getElementById('filter-form').submit(), 400);
                      }
                  }">

                <input type="hidden" name="filter_applied" value="1">
                @if ($sort !== 'name')  <input type="hidden" name="sort"  value="{{ $sort }}"> @endif
                @if ($order !== 'ASC')  <input type="hidden" name="order" value="{{ $order }}"> @endif

                {{-- Suchzeile + Filterbutton + Neu-Button --}}
                <div class="flex items-center justify-between mb-3 gap-3">
                    <div class="flex items-center gap-2 flex-1">
                        {{-- Suchfeld (Live-Suche) --}}
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}"
                                   placeholder="Name, Zweck, SG, Hersteller..."
                                   @input="debouncedSearch()"
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-72 pl-8" />
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                            </svg>
                        </div>

                        {{-- Filter-Toggle --}}
                        <button type="button" @click="filtersOpen = !filtersOpen"
                                :class="filtersOpen ? 'bg-indigo-50 text-indigo-700 border-indigo-300' : 'bg-white text-gray-600 border-gray-300'"
                                class="inline-flex items-center gap-1.5 px-3 py-2 border rounded-md text-sm font-medium hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filter
                            @if ($activeFilterCount > 0)
                                <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-bold bg-indigo-600 text-white rounded-full">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        {{-- Zurücksetzen --}}
                        @if ($search || $anyFilterActive)
                            <a href="{{ route('applikationen.index', ['reset' => '1']) }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50">
                                Zurücksetzen
                            </a>
                        @endif
                    </div>

                    @can('applikationen.create')
                    <a href="{{ route('applikationen.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Neue Applikation
                    </a>
                    @endcan
                </div>

                {{-- Aufklappbares Filterpanel --}}
                <div x-show="filtersOpen" x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-cloak
                     class="bg-white border border-gray-200 rounded-lg p-3 mb-4">

                    {{-- Reihe 1: Sachgebiet, Baustein, Administrator, Verantwortlicher --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Sachgebiet</label>
                            <select name="filter_abteilung_id" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach($abteilungen as $abt)
                                    <option value="{{ $abt->id }}" {{ $filterAbteilungId == $abt->id ? 'selected' : '' }}>
                                        {{ $abt->anzeigename }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Baustein</label>
                            <select name="filter_baustein" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::BAUSTEINE as $key => $label)
                                    <option value="{{ $key }}" {{ $filterBaustein === $key ? 'selected' : '' }}>{{ $key }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Administrator</label>
                            <select name="filter_admin_user_id" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                <option value="none" {{ $filterAdminUserId === 'none' ? 'selected' : '' }}>— Ohne Administrator —</option>
                                @foreach($adminUsers as $u)
                                    <option value="{{ $u->id }}" {{ $filterAdminUserId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Verantwortlicher</label>
                            <select name="filter_ohne_verantwortlich" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                <option value="1" {{ $filterOhneVerantwortlich ? 'selected' : '' }}>— Ohne Verantwortlichen —</option>
                            </select>
                        </div>
                    </div>

                    {{-- Reihe 2: Schutzbedarf + Offene Revision --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vertraulichkeit</label>
                            <select name="filter_confidentiality" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterConfidentiality === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Integrität</label>
                            <select name="filter_integrity" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterIntegrity === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Verfügbarkeit</label>
                            <select name="filter_availability" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterAvailability === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Revision</label>
                            <select name="filter_offene_revision" onchange="this.form.submit()"
                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm
                                           {{ $filterOffeneRevision ? 'border-red-300 bg-red-50 text-red-700' : '' }}">
                                <option value="">Alle</option>
                                <option value="1" {{ $filterOffeneRevision ? 'selected' : '' }}>Offene Revision (überfällig)</option>
                            </select>
                        </div>
                    </div>
                </div>

            </form>

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
                                    <a href="{{ route('applikationen.index', array_merge(request()->query(), ['sort' => $col, 'order' => $sort === $col ? $nextOrder : 'ASC'])) }}"
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
                                @php $revisionFaellig = $app->revision_date && $app->revision_date->isPast(); @endphp
                                <tr class="hover:bg-gray-50 {{ $revisionFaellig ? 'border-l-2 border-l-red-400' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $app->name }}</div>
                                        @if ($app->hersteller)
                                            <div class="text-xs text-gray-400 mt-0.5">{{ $app->hersteller }}</div>
                                        @endif
                                        @if ($app->einsatzzweck)
                                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($app->einsatzzweck, 60) }}</div>
                                        @endif
                                        @if ($revisionFaellig)
                                            <div class="mt-1">
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    Revision fällig seit {{ $app->revision_date->format('d.m.Y') }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($app->baustein)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">{{ $app->baustein }}</span>
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if ($app->abteilung)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In Abteilungsdatenbank gefunden"></span>
                                                {{ $app->abteilung->anzeigename }}
                                            </div>
                                        @elseif ($app->sg)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="Nicht in Abteilungsdatenbank zugeordnet"></span>
                                                {{ $app->sg }}
                                            </div>
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if ($app->verantwortlichAdUser)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In AD-Datenbank gefunden"></span>
                                                {{ $app->verantwortlichAdUser->anzeigenameOrName }}
                                            </div>
                                        @elseif ($app->verantwortlich_sg)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="Nicht in AD-Datenbank gefunden"></span>
                                                {{ $app->verantwortlich_sg }}
                                            </div>
                                        @elseif ($app->verantwortlich_ad_user_id)
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                                <span class="text-gray-400 italic text-xs">Verantwortl. nicht gefunden</span>
                                            </div>
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                        @if ($app->adminUser)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In Benutzerdatenbank gefunden"></span>
                                                <span class="text-xs text-gray-500">Admin: {{ $app->adminUser->name }}</span>
                                            </div>
                                        @elseif ($app->admin)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                                <span class="text-xs text-gray-500">Admin: {{ $app->admin }}</span>
                                            </div>
                                        @elseif ($app->admin_user_id)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500"></span>
                                                <span class="text-xs text-gray-400 italic">Admin nicht gefunden</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $sbLabels = ['confidentiality'=>'Vertraulichkeit','integrity'=>'Integrität','availability'=>'Verfügbarkeit'];
                                            $sbDot    = ['A'=>'bg-green-500','B'=>'bg-yellow-400','C'=>'bg-red-500'];
                                            $sbTxt    = ['A'=>'text-green-700','B'=>'text-yellow-700','C'=>'text-red-700'];
                                        @endphp
                                        <div class="space-y-0.5">
                                            @foreach($sbLabels as $field => $label)
                                                @php $val = $app->$field; @endphp
                                                <div class="flex items-center gap-1.5 text-xs">
                                                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $sbDot[$val] ?? 'bg-gray-400' }}"></span>
                                                    <span class="text-gray-400 w-24">{{ $label }}</span>
                                                    <span class="font-semibold {{ $sbTxt[$val] ?? 'text-gray-600' }}">{{ $val }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right" x-data="{ showDelete: false }">
                                        <div class="inline-flex items-center gap-1">
                                            @can('applikationen.edit')
                                            <a href="{{ route('applikationen.edit', $app) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
                                               title="Bearbeiten">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @endcan
                                            @can('applikationen.delete')
                                            <button @click="showDelete = true" type="button"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                                    title="Löschen">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
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
                                        </div>
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
