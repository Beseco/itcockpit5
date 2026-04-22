@php $versionId = $activeVersion?->id; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Haushaltsplanung &ndash; Dashboard</h2>
                    <span class="px-2 py-1 text-xs rounded-full {{ $budgetYear->status === 'approved' ? 'bg-green-100 text-green-800' : ($budgetYear->status === 'preliminary' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($budgetYear->status) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select onchange="submitYearForm(this.value)" class="border-gray-300 rounded text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($allBudgetYears as $by)
                            <option value="{{ $by->id }}" {{ $by->id == $budgetYear->id ? 'selected' : '' }}>
                                {{ $by->year }} ({{ ucfirst($by->status) }})
                            </option>
                        @endforeach
                    </select>
                    <form method="GET" action="{{ route('hh.dashboard.show', $budgetYear) }}" id="cc-form">
                        <select name="cost_center_id" onchange="document.getElementById('cc-form').submit()" class="border-gray-300 rounded text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Kostenstelle waehlen --</option>
                            @foreach($allCostCenters as $cc)
                                <option value="{{ $cc->id }}" {{ $selectedCostCenter?->id == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->number }} {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            @if($selectedCostCenter)
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('hh.dashboard.show', $budgetYear) }}" class="hover:text-blue-600">{{ $budgetYear->year }}</a>
                    <span>&rsaquo;</span>
                    <span class="text-gray-800 font-medium">{{ $selectedCostCenter->number }} {{ $selectedCostCenter->name }}</span>
                </nav>
            @endif
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))<div class="rounded bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="rounded bg-red-100 px-4 py-3 text-red-800">{{ session('error') }}</div>@endif

            {{-- Jahresübergreifende Positionssuche --}}
            <form method="GET" action="{{ route('hh.dashboard.search', $budgetYear) }}"
                  class="bg-white shadow rounded-lg p-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" name="q" placeholder="Position suchen (z.B. WLAN, Baramundi, Microsoft)…"
                       class="flex-1 border-0 text-sm focus:ring-0 outline-none text-gray-700 placeholder-gray-400">
                <button type="submit"
                        class="px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 transition whitespace-nowrap">
                    Suchen
                </button>
            </form>

            {{-- Sachkonto-Suche (Live) --}}
            <div class="bg-white shadow rounded-lg p-3 flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M3 8h18M3 12h12"/>
                </svg>
                <input type="text" id="acc-search" placeholder="Sachkonto filtern (Nummer oder Name)…"
                       class="flex-1 border-0 text-sm focus:ring-0 outline-none text-gray-700 placeholder-gray-400">
                <span id="acc-search-count" class="text-xs text-gray-400 hidden whitespace-nowrap"></span>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Gesamtbudget @if($selectedCostCenter)<span class="normal-case font-normal text-gray-400">({{ $selectedCostCenter->number }})</span>@endif</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($totals['total'] ?? 0, 0, ',', '.') }} &euro;</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Investiv</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format($totals['investiv'] ?? 0, 0, ',', '.') }} &euro;</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Konsumtiv</p>
                    <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format($totals['konsumtiv'] ?? 0, 0, ',', '.') }} &euro;</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Anteil Investiv</p>
                    @php $share = $totals['investive_share'] ?? 0; @endphp
                    <p class="mt-1 text-2xl font-semibold {{ $share > 80 ? 'text-red-600' : ($share > 50 ? 'text-yellow-600' : 'text-green-600') }}">{{ number_format($share, 1, ',', '.') }} %</p>
                </div>
            </div>

            @if(!$selectedCostCenter)
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-4 text-blue-700 text-sm">
                    Bitte eine Kostenstelle auswaehlen, um die Sachkonten anzuzeigen.
                </div>
            @else
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-800">Sachkonten &ndash; {{ $selectedCostCenter->number }} {{ $selectedCostCenter->name }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Positionen</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Summe (&euro;)</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($accountsWithTotals as $row)
                                    <tr class="hover:bg-gray-50" data-search="{{ $row['account']->number }} {{ $row['account']->name }}">
                                        <td class="px-6 py-3 font-mono">
                                            <a href="{{ route('hh.dashboard.account-positions', [$budgetYear, $selectedCostCenter, $row['account']]) }}" class="text-blue-700 hover:underline">
                                                {{ $row['account']->number }} {{ $row['account']->name }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs {{ $row['account']->type === 'investiv' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800' }}">
                                                {{ $row['account']->type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-gray-600">{{ $row['count'] }}</td>
                                        <td class="px-6 py-3 text-right font-medium {{ $row['total'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                            {{ number_format($row['total'], 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-400 text-xs">&#8250;</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Keine Sachkonten vorhanden.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        var accSearchInput = document.getElementById('acc-search');
        if (accSearchInput) {
            accSearchInput.addEventListener('input', function () {
                var term = this.value.toLowerCase();
                var rows = document.querySelectorAll('tbody tr[data-search]');
                var visible = 0;
                rows.forEach(function (row) {
                    var show = !term || row.dataset.search.toLowerCase().includes(term);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                var counter = document.getElementById('acc-search-count');
                if (term) {
                    counter.textContent = visible + ' Treffer';
                    counter.classList.remove('hidden');
                } else {
                    counter.classList.add('hidden');
                }
            });
        }

        function submitYearForm(yearId) {
            var ccEl = document.querySelector('[name="cost_center_id"]');
            var ccId = ccEl ? ccEl.value : '';
            var base = '{{ rtrim(route("hh.dashboard.show", ["budgetYear" => "__ID__"]), "/") }}'.replace('__ID__', yearId);
            window.location.href = base + (ccId ? '?cost_center_id=' + ccId : '');
        }
    </script>
</x-app-layout>
