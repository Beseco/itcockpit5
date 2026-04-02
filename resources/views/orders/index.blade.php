<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellverwaltung
        </h2>
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

            {{-- Dashboard: Obligo + KST-Summen --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Obligo-Box --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-indigo-500">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Obligo (offen)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ number_format($obligo, 2, ',', '.') }} €
                    </p>
                    <p class="text-xs text-gray-400 mt-1">alle Status außer „angeordnet"</p>
                </div>

                {{-- KST-Summen-Boxen --}}
                @foreach ($kstSummen as $kst)
                    <a href="{{ route('orders.index', ['cost_center_id' => $kst->id]) }}"
                       class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border-l-4 border-blue-400 hover:bg-blue-50 transition">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">KST {{ $kst->number }}</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">
                            {{ number_format($kst->summe, 2, ',', '.') }} €
                        </p>
                        <p class="text-xs text-gray-400 mt-1 truncate">{{ $kst->description }}</p>
                        <p class="text-xs text-indigo-400 mt-0.5">offene Bestellungen</p>
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
                            </h3>
                            <a href="{{ route('orders.index') }}" class="text-sm text-gray-400 hover:text-gray-600">
                                ✕ Schließen
                            </a>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sachkonto</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Summe</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($kstAccounts as $acc)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $acc->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $acc->description }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-semibold">
                                            {{ number_format($acc->summe, 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-center text-gray-400">Keine Einträge.</td>
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
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="filter_status"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Alle Status</option>
                            <option value="nicht_angeordnet" {{ $filterStatus === 'nicht_angeordnet' ? 'selected' : '' }}>Nicht angeordnet</option>
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
                        @if ($filterStatus !== 'nicht_angeordnet' || $filterOwn || $filterDateFrom !== '' || $filterDateTo !== '' || $search !== '')
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Händler</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Käufer</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Betrag</th>
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
                                            @if($canEdit)
                                            <a href="{{ route('orders.edit', $order) }}"
                                               class="text-indigo-600 hover:text-indigo-900 mr-3">Bearbeiten</a>
                                            @endif

                                            @if($canDelete)
                                            <button @click="showDelete = true" type="button"
                                                    class="text-red-600 hover:text-red-900">Löschen</button>

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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                            Noch keine Bestellungen vorhanden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($orders->hasPages())
                        <div class="mt-4">
                            {{ $orders->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
