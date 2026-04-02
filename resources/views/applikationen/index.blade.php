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

            <div id="app-table">
                @include('applikationen._table')
            </div>

        </div>
    </div>
@push('scripts')
<script>
(function () {
    var form      = document.getElementById('filter-form');
    var container = document.getElementById('app-table');
    if (!form || !container) return;

    var base        = '{{ route("applikationen.index") }}';
    var searchTimer = null;

    /* Formularwerte als URLSearchParams einlesen (ohne FormData-Bugs) */
    function collectParams() {
        var p = new URLSearchParams();
        Array.from(form.elements).forEach(function (el) {
            if (!el.name || el.disabled) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            p.set(el.name, el.value);
        });
        p.set('filter_applied', '1');
        p.set('_ajax', '1');
        return p;
    }

    /* Tabelle per AJAX laden */
    function loadTable(url) {
        container.style.opacity = '0.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                /* Redirect (z. B. Session-Restore) → normale Navigation */
                if (r.redirected) { window.location.href = r.url; return null; }
                return r.text();
            })
            .then(function (html) {
                if (html === null) return;
                /* Sicherheits-Fallback: wenn versehentlich ganze Seite zurückkommt */
                if (/^\s*<!/.test(html) || /^\s*<html/i.test(html)) {
                    var cleanUrl = url.replace(/[?&]_ajax=1/, '');
                    window.location.href = cleanUrl;
                    return;
                }
                container.innerHTML = html;
                container.style.opacity = '1';
                /* URL sauber halten (ohne _ajax=1) */
                var pu = new URL(url, window.location.origin);
                pu.searchParams.delete('_ajax');
                history.pushState(null, '', pu.toString());
                bindLinks();
                if (window.Alpine) window.Alpine.initTree(container);
            })
            .catch(function () { container.style.opacity = '1'; });
    }

    function submitForm() {
        loadTable(base + '?' + collectParams().toString());
    }

    /* Selects: sofort auslösen */
    form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', submitForm);
    });

    /* Suchfeld: 400 ms Debounce */
    var searchEl = document.getElementById('app-search-input');
    if (searchEl) {
        searchEl.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(submitForm, 400);
        });
    }

    /* Sort- und Paginierungslinks in der Tabelle abfangen */
    function bindLinks() {
        container.querySelectorAll('a.app-table-link, .app-table-pagination a').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var u = new URL(this.href, window.location.origin);
                u.searchParams.set('_ajax', '1');
                loadTable(u.toString());
            });
        });
    }

    bindLinks();
}());
</script>
@endpush
</x-app-layout>
