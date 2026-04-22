<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applikationen</h2>
                <a href="{{ route('applikationen.help') }}" title="Hilfe & Anleitung"
                   class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </a>
            </div>
            @can('applikationen.edit')
                <a href="{{ route('applikationen.revision-settings') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Revisions-Erinnerung
                </a>
            @endcan
        </div>
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
                  x-data="{ filtersOpen: {{ $anyFilterActive ? 'true' : 'false' }} }">

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
                                   id="app-search-input"
                                   class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-72 pl-8" />
                            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                            </svg>
                        </div>

                        {{-- Suchen-Button --}}
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                            Suchen
                        </button>

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

                    @php
                        $exportParams = array_filter([
                            'search'                     => $search,
                            'filter_abteilung_id'        => $filterAbteilungId,
                            'filter_baustein'            => $filterBaustein,
                            'filter_admin_user_id'       => $filterAdminUserId,
                            'filter_ohne_verantwortlich' => $filterOhneVerantwortlich ? '1' : '',
                            'filter_confidentiality'     => $filterConfidentiality,
                            'filter_integrity'           => $filterIntegrity,
                            'filter_availability'        => $filterAvailability,
                            'filter_offene_revision'     => $filterOffeneRevision ? '1' : '',
                        ], fn($v) => $v !== '' && $v !== null && $v !== false);
                        $exportQuery = http_build_query($exportParams);
                    @endphp

                    {{-- Export-Buttons --}}
                    <a href="{{ route('applikationen.export.xlsx') . ($exportQuery ? '?' . $exportQuery : '') }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-green-50 hover:border-green-400 hover:text-green-700 transition-colors"
                       title="Als Excel exportieren">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Excel
                    </a>

                    <a href="{{ route('applikationen.export.pdf') . ($exportQuery ? '?' . $exportQuery : '') }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-red-50 hover:border-red-400 hover:text-red-700 transition-colors"
                       title="Als PDF exportieren">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        PDF
                    </a>

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
                            <select name="filter_abteilung_id"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                            <select name="filter_baustein"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::BAUSTEINE as $key => $label)
                                    <option value="{{ $key }}" {{ $filterBaustein === $key ? 'selected' : '' }}>{{ $key }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Administrator</label>
                            <select name="filter_admin_user_id"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                <option value="none" {{ $filterAdminUserId === 'none' ? 'selected' : '' }}>— Ohne Administrator —</option>
                                @foreach($adminUsers as $u)
                                    <option value="{{ $u->id }}" {{ $filterAdminUserId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Verantwortlicher</label>
                            <select name="filter_ohne_verantwortlich"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                <option value="1" {{ $filterOhneVerantwortlich ? 'selected' : '' }}>— Ohne Verantwortlichen —</option>
                            </select>
                        </div>
                    </div>

                    {{-- Reihe 2: Schutzbedarf + Offene Revision --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Vertraulichkeit</label>
                            <select name="filter_confidentiality"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterConfidentiality === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Integrität</label>
                            <select name="filter_integrity"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterIntegrity === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Verfügbarkeit</label>
                            <select name="filter_availability"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Alle</option>
                                @foreach(\App\Models\Applikation::SCHUTZBEDARF as $key => $label)
                                    <option value="{{ $key }}" {{ $filterAvailability === $key ? 'selected' : '' }}>{{ $key }} – {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Revision</label>
                            <select name="filter_offene_revision"                                    class="block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm
                                           {{ $filterOffeneRevision ? 'border-red-300 bg-red-50 text-red-700' : '' }}">
                                <option value="">Alle</option>
                                <option value="1" {{ $filterOffeneRevision ? 'selected' : '' }}>Offene Revision (überfällig)</option>
                            </select>
                        </div>
                    </div>
                </div>

            </form>

            @include('applikationen._table')

        </div>
    </div>
@push('scripts')
<script>
(function () {
    var form = document.getElementById('filter-form');
    if (!form) return;

    /* Nur nicht-leere Werte in die URL aufnehmen */
    function cleanSubmit(e) {
        e.preventDefault();
        var params = new URLSearchParams();
        Array.from(form.elements).forEach(function (el) {
            if (!el.name || el.disabled) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            if (el.value !== '') params.set(el.name, el.value);
        });
        params.set('filter_applied', '1');
        window.location.href = form.action + '?' + params.toString();
    }

    form.addEventListener('submit', cleanSubmit);

    /* Selects: sofort */
    form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', function () { form.requestSubmit(); });
    });
}());
</script>
@endpush
</x-app-layout>
