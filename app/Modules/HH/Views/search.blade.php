<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Suche – {{ $budgetYear->year }}
            </h2>
            <div class="flex items-center gap-3">
                <select onchange="window.location.href='{{ route('hh.dashboard.search', ['budgetYear' => '__ID__']) }}'.replace('__ID__', this.value) + (document.getElementById('q-input').value ? '?q=' + encodeURIComponent(document.getElementById('q-input').value) : '')"
                        class="border-gray-300 rounded text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($allBudgetYears as $by)
                        <option value="{{ $by->id }}" {{ $by->id == $budgetYear->id ? 'selected' : '' }}>
                            {{ $by->year }} ({{ match($by->status) { 'draft' => 'Entwurf', 'preliminary' => 'Vorläufig', 'approved' => 'Genehmigt', default => $by->status } }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Suchformular --}}
            <form method="GET" action="{{ route('hh.dashboard.search', $budgetYear) }}"
                  class="bg-white shadow rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" id="q-input" name="q" value="{{ $q }}"
                           placeholder="Name oder Beschreibung (z.B. WLAN, Baramundi)…"
                           autofocus
                           class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Priorität</label>
                        <select name="priority" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Alle</option>
                            @foreach(['hoch','mittel','niedrig'] as $p)
                                <option value="{{ $p }}" {{ $priority === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Alle</option>
                            @foreach(['geplant','angepasst','gestrichen'] as $s)
                                <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Betrag von (€)</label>
                        <input type="number" name="amount_min" value="{{ $amountMin }}" min="0" step="0.01"
                               placeholder="0"
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Betrag bis (€)</label>
                        <input type="number" name="amount_max" value="{{ $amountMax }}" min="0" step="0.01"
                               placeholder="unbegrenzt"
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-5 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition">
                        Suchen
                    </button>
                    @if($hasFilter)
                        <a href="{{ route('hh.dashboard.search', $budgetYear) }}"
                           class="text-sm text-gray-400 hover:text-gray-600">Filter zurücksetzen</a>
                    @endif
                </div>
            </form>

            @if(!$hasFilter)
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-6 py-4 text-blue-700 text-sm">
                    Filter ausfüllen um Positionen in Haushaltsjahr {{ $budgetYear->year }} zu finden.
                </div>
            @elseif($positions->isEmpty())
                <div class="bg-gray-50 border border-gray-200 rounded-lg px-6 py-4 text-gray-600 text-sm">
                    Keine Positionen für <strong>„{{ $q }}"</strong> in {{ $budgetYear->year }} gefunden.
                </div>
            @else
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-sm text-gray-600">
                            <strong>{{ $positions->count() }}</strong> Treffer für
                            <span class="font-medium text-indigo-700">„{{ $q }}"</span>
                            in {{ $budgetYear->year }}
                        </span>
                        <span class="text-sm font-semibold text-gray-800">
                            Gesamt: {{ number_format($positions->sum('amount'), 2, ',', '.') }} €
                        </span>
                    </div>
                    @php
                        $sortParams = request()->except(['sort','dir']);
                        $sortLink = fn(string $field) =>
                            route('hh.dashboard.search', $budgetYear) . '?' . http_build_query(
                                array_merge($sortParams, [
                                    'sort' => $field,
                                    'dir'  => ($sortField === $field && $sortDir === 'asc') ? 'desc' : 'asc',
                                ])
                            );
                        $sortIcon = fn(string $field) =>
                            $sortField === $field
                                ? ($sortDir === 'asc' ? ' ↑' : ' ↓')
                                : '';
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kostenstelle</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ $sortLink('project_name') }}" class="hover:text-indigo-600">
                                            Name{!! $sortIcon('project_name') !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ $sortLink('amount') }}" class="hover:text-indigo-600">
                                            Betrag (€){!! $sortIcon('amount') !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ $sortLink('priority') }}" class="hover:text-indigo-600">
                                            Priorität{!! $sortIcon('priority') !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ $sortLink('status') }}" class="hover:text-indigo-600">
                                            Status{!! $sortIcon('status') !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wiederk.</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($positions as $pos)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                            {{ $pos->costCenter->number }} {{ $pos->costCenter->name }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                            {{ $pos->account->number }} {{ $pos->account->name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $highlighted = e($pos->project_name);
                                                $highlighted = preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark class="bg-yellow-100 rounded px-0.5">$1</mark>', $highlighted);
                                            @endphp
                                            {!! $highlighted !!}
                                            @if($pos->description)
                                                <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $pos->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 whitespace-nowrap">
                                            {{ number_format($pos->amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-full text-xs
                                                {{ $pos->status === 'geplant' ? 'bg-blue-100 text-blue-800' : ($pos->status === 'angepasst' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-700') }}">
                                                {{ $pos->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                            {{ $pos->is_recurring ? 'Ja' : 'Nein' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($activeVersion)
                                                <a href="{{ route('hh.dashboard.account-positions', [$budgetYear, $pos->costCenter, $pos->account]) }}"
                                                   class="text-indigo-600 hover:underline text-xs">
                                                    → Sachkonto
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-700">Gesamt</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">
                                        {{ number_format($positions->sum('amount'), 2, ',', '.') }} €
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
