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
            <div class="flex items-center justify-between mb-3 gap-4">
                <form action="{{ route('applikationen.index') }}" method="GET" class="flex gap-2" id="search-form">
                    <input type="hidden" name="filter_applied" value="1">
                    <x-text-input name="search" type="text" placeholder="Name, Zweck, SG, Hersteller..."
                                  value="{{ $search }}" class="w-72" />
                    {{-- Filter-Werte als Hidden-Inputs für Suche --}}
                    @if($filterAbteilungId)        <input type="hidden" name="filter_abteilung_id"        value="{{ $filterAbteilungId }}"> @endif
                    @if($filterBaustein)           <input type="hidden" name="filter_baustein"            value="{{ $filterBaustein }}"> @endif
                    @if($filterAdminUserId)        <input type="hidden" name="filter_admin_user_id"       value="{{ $filterAdminUserId }}"> @endif
                    @if($filterOhneVerantwortlich) <input type="hidden" name="filter_ohne_verantwortlich" value="1"> @endif
                    @if($filterConfidentiality)    <input type="hidden" name="filter_confidentiality"     value="{{ $filterConfidentiality }}"> @endif
                    @if($filterIntegrity)          <input type="hidden" name="filter_integrity"           value="{{ $filterIntegrity }}"> @endif
                    @if($filterAvailability)       <input type="hidden" name="filter_availability"        value="{{ $filterAvailability }}"> @endif
                    @if ($sort !== 'name')         <input type="hidden" name="sort"  value="{{ $sort }}"> @endif
                    @if ($order !== 'ASC')         <input type="hidden" name="order" value="{{ $order }}"> @endif
                    <x-primary-button type="submit">Suchen</x-primary-button>
                    @if ($search || $filterAbteilungId || $filterBaustein || $filterAdminUserId || $filterOhneVerantwortlich || $filterConfidentiality || $filterIntegrity || $filterAvailability)
                        <a href="{{ route('applikationen.index', ['reset' => '1']) }}"
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

            {{-- Filterleiste --}}
            <form action="{{ route('applikationen.index') }}" method="GET"
                  class="bg-white border border-gray-200 rounded-lg p-3 mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-2">
                <input type="hidden" name="filter_applied" value="1">
                @if ($sort !== 'name')  <input type="hidden" name="sort"  value="{{ $sort }}"> @endif
                @if ($order !== 'ASC')  <input type="hidden" name="order" value="{{ $order }}"> @endif
                @if ($search !== '')    <input type="hidden" name="search" value="{{ $search }}"> @endif

                {{-- Sachgebiet --}}
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

                {{-- Baustein --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Baustein</label>
                    <select name="filter_baustein" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        @foreach(\App\Models\Applikation::BAUSTEINE as $key => $label)
                            <option value="{{ $key }}" {{ $filterBaustein === $key ? 'selected' : '' }}>
                                {{ $key }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Administrator --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Administrator</label>
                    <select name="filter_admin_user_id" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        <option value="none" {{ $filterAdminUserId === 'none' ? 'selected' : '' }}>— Ohne Administrator —</option>
                        @foreach($adminUsers as $u)
                            <option value="{{ $u->id }}" {{ $filterAdminUserId == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Verantwortlicher --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Verantwortlicher</label>
                    <select name="filter_ohne_verantwortlich" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        <option value="1" {{ $filterOhneVerantwortlich ? 'selected' : '' }}>— Ohne Verantwortlichen —</option>
                    </select>
                </div>

                {{-- Vertraulichkeit --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Vertraulichkeit</label>
                    <select name="filter_confidentiality" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                            <option value="{{ $key }}" {{ $filterConfidentiality === $key ? 'selected' : '' }}>
                                {{ $key }} – {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Integrität --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Integrität</label>
                    <select name="filter_integrity" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                            <option value="{{ $key }}" {{ $filterIntegrity === $key ? 'selected' : '' }}>
                                {{ $key }} – {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Verfügbarkeit --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Verfügbarkeit</label>
                    <select name="filter_availability" onchange="this.form.submit()"
                            class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Alle</option>
                        @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                            <option value="{{ $key }}" {{ $filterAvailability === $key ? 'selected' : '' }}>
                                {{ $key }} – {{ $label }}
                            </option>
                        @endforeach
                    </select>
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
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{-- Sachgebiet: grün wenn DB-verknüpft, rot wenn nur Legacy-Text --}}
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
                                        {{-- Verfahrensverantwortlicher --}}
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
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="AD-Benutzer nicht mehr in Datenbank gefunden"></span>
                                                <span class="text-gray-400 italic text-xs">Verantwortl. nicht gefunden</span>
                                            </div>
                                        @else
                                            <span class="text-gray-300">–</span>
                                        @endif
                                        {{-- IT-Administrator --}}
                                        @if ($app->adminUser)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-green-500" title="In Benutzerdatenbank gefunden"></span>
                                                <span class="text-xs text-gray-500">Admin: {{ $app->adminUser->name }}</span>
                                            </div>
                                        @elseif ($app->admin)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="Nicht in Benutzerdatenbank gefunden"></span>
                                                <span class="text-xs text-gray-500">Admin: {{ $app->admin }}</span>
                                            </div>
                                        @elseif ($app->admin_user_id)
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="w-2 h-2 rounded-full flex-shrink-0 bg-red-500" title="Admin-Benutzer nicht mehr in Datenbank gefunden"></span>
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
