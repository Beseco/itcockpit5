<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bestellverwaltung</h2>
            <a href="{{ route('orders.help') }}" title="Hilfe & Anleitung" class="inline-flex items-center justify-center w-7 h-7 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-full transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Erfolgsmeldungen --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Haushaltsjahr-Umschalter --}}
            <div class="flex items-center gap-2 mb-4">
                <span class="text-sm text-gray-500 font-medium">Haushaltsjahr:</span>
                @foreach ($availableBudgetYears as $yr)
                    <a href="{{ route('orders.index', array_merge(request()->except('budget_year', 'page'), ['budget_year' => $yr])) }}"
                       class="px-3 py-1 rounded text-sm font-medium {{ $filterBudgetYear == $yr ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        {{ $yr }}
                    </a>
                @endforeach
            </div>

            {{-- Dashboard: Obligo + KST-Summen --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Obligo-Box --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-indigo-500">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Obligo {{ $filterBudgetYear }} (offen)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ number_format($obligo, 0, ',', '.') }} €
                    </p>
                    <p class="text-xs text-gray-400 mt-1">alle Status außer „angeordnet"</p>
                </div>

                {{-- KST-Summen-Boxen --}}
                @foreach ($kstSummen as $kst)
                    <a href="{{ route('orders.index', ['cost_center_id' => $kst->id, 'budget_year' => $filterBudgetYear]) }}"
                       class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-blue-400 hover:bg-blue-50 transition">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">KST {{ $kst->number }}</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">
                            {{ number_format($kst->summe, 0, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-400 mt-1 truncate">{{ $kst->description }}</p>
                        <p class="text-xs text-indigo-400 mt-0.5">offene Bestellungen {{ $filterBudgetYear }}</p>
                    </a>
                @endforeach
            </div>

            {{-- KST-Detail (wenn ausgewählt) --}}
            @if ($kstDetails)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                KST {{ $kstDetails->number }} — {{ $kstDetails->description }}
                                <span class="text-sm font-normal text-gray-500 ml-2">Haushaltsjahr {{ $filterBudgetYear }}</span>
                            </h3>
                            <a href="{{ route('orders.index', ['budget_year' => $filterBudgetYear]) }}" class="text-sm text-gray-400 hover:text-gray-600">
                                ✕ Schließen
                            </a>
                        </div>

                        @if (!empty($hhBudgetData))
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">HH-Budget {{ $filterBudgetYear }}</p>
                            <table class="min-w-full divide-y divide-gray-200 mb-6">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Geplant</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bestellt</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Verfügbar</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Auslastung</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($hhBudgetData as $row)
                                        @php $pct = $row['planned'] > 0 ? min(100, round(($row['obligo'] / $row['planned']) * 100)) : 0; @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['account_number'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row['account_name'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($row['planned'], 0, ',', '.') }} €</td>
                                            <td class="px-4 py-3 text-sm text-right {{ $row['obligo'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                                                {{ number_format($row['obligo'], 0, ',', '.') }} €
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $row['available'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                                                {{ number_format($row['available'], 0, ',', '.') }} €
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                        <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 75 ? 'bg-orange-400' : 'bg-green-500') }}"
                                                             style="width: {{ $pct }}%"></div>
                                                    </div>
                                                    <span class="text-xs text-gray-500 w-8">{{ $pct }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Offene Bestellungen</p>
                        @endif

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Obligo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($kstAccounts as $acc)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $acc->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $acc->description }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-semibold">
                                            {{ number_format($acc->summe, 0, ',', '.') }} €
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-center text-gray-400">Keine offenen Bestellungen.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Filter --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 p-4">
                <form action="{{ route('orders.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                    @if (request('cost_center_id'))
                        <input type="hidden" name="cost_center_id" value="{{ request('cost_center_id') }}">
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Artikel, Händler, Käufer …"
                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm w-56">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kostenstelle</label>
                        <select name="filter_cost_center_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Kostenstellen</option>
                            @foreach ($allCostCenters as $cc)
                                <option value="{{ $cc->id }}" {{ $filterCostCenter == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->number }} {{ $cc->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sachkonto</label>
                        <select name="filter_account_code_id"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Sachkonten</option>
                            @foreach ($allAccountCodes as $ac)
                                <option value="{{ $ac->id }}" {{ $filterAccountCode == $ac->id ? 'selected' : '' }}>
                                    {{ $ac->code }} {{ $ac->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="filter_status"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Status</option>
                            @foreach (\App\Models\Order::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" {{ $filterStatus == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Datum von</label>
                        <input type="date" name="date_from" value="{{ $filterDateFrom }}"
                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Datum bis</label>
                        <input type="date" name="date_to" value="{{ $filterDateTo }}"
                               class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex items-center gap-2 pb-0.5">
                        <input type="checkbox" id="filter_own" name="filter_own" value="1"
                               {{ $filterOwn ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="filter_own" class="text-sm text-gray-700 cursor-pointer select-none">Nur eigene</label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                            Filtern
                        </button>
                        @if ($filterStatus !== '' || $filterOwn || $filterDateFrom !== '' || $filterDateTo !== '' || $search !== '' || $filterAccountCode > 0 || $filterCostCenter > 0)
                            <a href="{{ route('orders.index', request()->only('cost_center_id')) }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                Zurücksetzen
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Neu-Button + Tabelle --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Alle Bestellungen
                            @if ($orders->total() !== $orders->count())
                                <span class="text-sm font-normal text-gray-400">({{ $orders->total() }} gesamt)</span>
                            @endif
                        </h3>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermissionTo('cost-centers.view'))
                            <a href="{{ route('cost-centers.index') }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Kostenstellen
                            </a>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermissionTo('account-codes.view'))
                            <a href="{{ route('account-codes.index') }}"
                               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M12 7h.01M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-4m-5 0V1m0 2h4V1m-4 0h4"/>
                                </svg>
                                Sachkonten
                            </a>
                            @endif
                            @can('orders.create')
                            <a href="{{ route('orders.import') }}"
                               class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700">
                                CSV Import
                            </a>
                            <a href="{{ route('orders.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Neue Bestellung
                            </a>
                            @endcan
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @php
                                        function orderSortUrl($field, $cur, $dir) {
                                            $newDir = ($cur === $field && $dir === 'desc') ? 'asc' : 'desc';
                                            return route('orders.index', array_merge(request()->except(['sort','dir','page']), ['sort' => $field, 'dir' => $newDir]));
                                        }
                                        function orderSortIcon($field, $cur, $dir) {
                                            return $field === $cur ? ($dir === 'desc' ? ' ↓' : ' ↑') : '';
                                        }
                                    @endphp
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ orderSortUrl('order_date', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                            Datum{!! orderSortIcon('order_date', $sortField, $sortDir) !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kostenstelle</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Händler</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Käufer</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        <a href="{{ orderSortUrl('price_gross', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                            Betrag{!! orderSortIcon('price_gross', $sortField, $sortDir) !!}
                                        </a>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $order->order_date->format('d.m.Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $order->subject }}
                                            @if ($order->quantity > 1)
                                                <span class="text-gray-400 text-xs">({{ $order->quantity }}x)</span>
                                            @endif
                                            @if($order->hhBudgetPosition)
                                                <p class="text-xs text-indigo-600 mt-0.5">&#128196; {{ $order->hhBudgetPosition->project_name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $order->costCenter?->number ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                            {{ $order->accountCode?->code ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $order->vendor?->firmenname ?? '–' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $order->buyer_username }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right whitespace-nowrap font-medium">
                                            {{ number_format($order->price_gross, 2, ',', '.') }} €
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $order->status == 6
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $order->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" x-data="{ showDelete: false }">
                                            @php
                                                $canEdit   = auth()->user()->hasModulePermission('orders', 'edit')   || (auth()->user()->hasModulePermission('orders', 'create') && $order->isOwnedBy(auth()->id()));
                                                $canDelete = auth()->user()->hasModulePermission('orders', 'delete') || (auth()->user()->hasModulePermission('orders', 'create') && $order->isOwnedBy(auth()->id()));
                                            @endphp
                                            <div class="inline-flex items-center gap-1">
                                            @if($canEdit)
                                            <a href="{{ route('orders.edit', $order) }}"
                                               class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200"
                                               title="Bearbeiten">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            @endif

                                            @if($canDelete)
                                            <button @click="showDelete = true" type="button"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200"
                                                    title="Löschen">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>

                                            {{-- Lösch-Modal --}}
                                            <div x-show="showDelete" x-cloak @click.away="showDelete = false"
                                                 class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
                                                <div class="flex items-center justify-center min-h-screen px-4">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                                                    <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Bestellung löschen</h3>
                                                        <p class="text-sm text-gray-500 mb-4">
                                                            Soll „{{ $order->subject }}" wirklich gelöscht werden? Diese Aktion kann nicht rückgängig gemacht werden.
                                                        </p>
                                                        <div class="flex justify-end space-x-3">
                                                            <button @click="showDelete = false" type="button"
                                                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                                                Abbrechen
                                                            </button>
                                                            <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                                    Löschen
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                            Noch keine Bestellungen vorhanden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between flex-wrap gap-2">
                        <x-per-page-select :per-page="$perPage" />
                        @if ($orders->hasPages())
                            <div>{{ $orders->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
