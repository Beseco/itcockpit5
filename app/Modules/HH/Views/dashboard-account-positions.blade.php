@php $versionId = $activeVersion?->id; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('hh.dashboard.show', [$budgetYear, 'cost_center_id' => $costCenter->id]) }}" class="hover:text-blue-600">{{ $costCenter->number }} {{ $costCenter->name }}</a>
                <span>&rsaquo;</span>
                <a href="{{ route('hh.dashboard.show', $budgetYear) }}" class="hover:text-blue-600">{{ $budgetYear->year }}</a>
                <span>&rsaquo;</span>
                <span class="text-gray-800 font-medium">{{ $account->number }} {{ $account->name }}</span>
            </nav>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Positionen &ndash; {{ $account->number }} {{ $account->name }}
                </h2>
                @if($canWrite && $versionId)
                    <button onclick="document.getElementById('modal-create-pos').classList.remove('hidden')"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                        + Neue Position
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="rounded bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded bg-red-100 px-4 py-3 text-red-800">{{ session('error') }}</div>
            @endif

            {{-- Budget-Summary --}}
            @php
                $summaryPct = $plannedTotal > 0 ? min(100, round((($obligo + $ausgaben) / $plannedTotal) * 100)) : 0;
                $ordersBase = $itCostCenterId && $itAccountCodeId
                    ? route('orders.index', ['filter_cost_center_id' => $itCostCenterId, 'filter_account_code_id' => $itAccountCodeId, 'filter_status' => 6, 'budget_year' => $budgetYear->year, 'sort' => 'price_gross', 'dir' => 'desc'])
                    : null;
            @endphp
            <div class="bg-white shadow rounded-lg p-5">
                <p class="text-xs text-gray-400 mb-3">
                    <span class="font-medium text-gray-600">KST {{ $costCenter->number }} {{ $costCenter->name }}</span>
                    <span class="mx-2 text-gray-300">|</span>
                    <span class="font-medium text-gray-600">{{ $account->number }} {{ $account->name }}</span>
                    <span class="mx-2 text-gray-300">|</span>
                    {{ $budgetYear->year }}
                </p>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Geplant</p>
                        <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($plannedTotal, 0, ',', '.') }} &euro;</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Bestellt (Obligo)</p>
                        @php $obligoUrl = $itCostCenterId && $itAccountCodeId
                            ? route('orders.index', ['filter_cost_center_id' => $itCostCenterId, 'filter_account_code_id' => $itAccountCodeId, 'filter_status' => 'nicht_angeordnet', 'budget_year' => $budgetYear->year, 'sort' => 'price_gross', 'dir' => 'desc'])
                            : null; @endphp
                        @if($obligoUrl && $obligo > 0)
                            <a href="{{ $obligoUrl }}" class="mt-1 text-xl font-semibold text-orange-600 hover:underline block">
                                {{ number_format($obligo, 0, ',', '.') }} &euro;
                            </a>
                        @else
                            <p class="mt-1 text-xl font-semibold text-orange-600">{{ number_format($obligo, 0, ',', '.') }} &euro;</p>
                        @endif
                        <p class="text-xs text-gray-400">offene Bestellungen {{ $budgetYear->year }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Ausgaben (bezahlt)</p>
                        @if($ordersBase && $ausgaben > 0)
                            <a href="{{ $ordersBase }}"
                               class="mt-1 text-xl font-semibold text-red-600 hover:underline block">
                                {{ number_format($ausgaben, 0, ',', '.') }} &euro;
                            </a>
                        @else
                            <p class="mt-1 text-xl font-semibold text-red-600">{{ number_format($ausgaben, 0, ',', '.') }} &euro;</p>
                        @endif
                        <p class="text-xs text-gray-400">angeordnete Rechnungen {{ $budgetYear->year }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Verfügbar</p>
                        <p class="mt-1 text-xl font-semibold {{ $availableBudget < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ number_format($availableBudget, 0, ',', '.') }} &euro;
                        </p>
                        <p class="text-xs text-gray-400">Budget − Obligo − Ausgaben</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $summaryPct >= 100 ? 'bg-red-500' : ($summaryPct >= 75 ? 'bg-orange-400' : 'bg-green-500') }}"
                             style="width: {{ $summaryPct }}%"></div>
                    </div>
                    <span class="text-sm text-gray-500 w-12 text-right">{{ $summaryPct }}%</span>
                </div>
            </div>

            {{-- Filter --}}
            @php $hasFilter = $searchQ !== '' || $amountMin !== null || $amountMax !== null; @endphp
            <form method="GET" action="{{ request()->url() }}" class="bg-white shadow rounded-lg p-3 flex flex-wrap items-end gap-3">
                <input type="hidden" name="sort" value="{{ $sortField }}">
                <input type="hidden" name="dir"  value="{{ $sortDir }}">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Positionsname</label>
                    <input type="text" name="q" value="{{ $searchQ }}" placeholder="suchen…"
                           class="border-gray-300 rounded text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 w-48">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Betrag von (€)</label>
                    <input type="number" name="amount_min" value="{{ $amountMin ?? '' }}" step="1" min="0" placeholder="0"
                           class="border-gray-300 rounded text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 w-28">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Betrag bis (€)</label>
                    <input type="number" name="amount_max" value="{{ $amountMax ?? '' }}" step="1" min="0" placeholder="unbegrenzt"
                           class="border-gray-300 rounded text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 w-28">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded hover:bg-indigo-700 uppercase tracking-wider">
                        Filtern
                    </button>
                    @if($hasFilter)
                        <a href="{{ request()->url() }}?sort={{ $sortField }}&dir={{ $sortDir }}"
                           class="px-3 py-2 bg-white border border-gray-300 text-xs font-semibold rounded text-gray-700 hover:bg-gray-50 uppercase tracking-wider">
                            Zurücksetzen
                        </a>
                    @endif
                </div>
                @if($hasFilter)
                    <span class="text-xs text-gray-400 self-center">{{ $positions->count() }} Treffer</span>
                @endif
            </form>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        @php
                            $apBase   = url()->current();
                            $apParams = request()->except(['sort', 'dir']);
                            function apSortUrl($base, $params, $field, $cur, $dir) {
                                return $base . '?' . http_build_query(array_merge($params, [
                                    'sort' => $field,
                                    'dir'  => ($cur === $field && $dir === 'asc') ? 'desc' : 'asc',
                                ]));
                            }
                            function apSortIcon($field, $cur, $dir) {
                                return $field === $cur ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
                            }
                        @endphp
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ apSortUrl($apBase, $apParams, 'project_name', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                        Projektname{!! apSortIcon('project_name', $sortField, $sortDir) !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ apSortUrl($apBase, $apParams, 'priority', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                        Priorität{!! apSortIcon('priority', $sortField, $sortDir) !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategorie</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ apSortUrl($apBase, $apParams, 'status', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                        Status{!! apSortIcon('status', $sortField, $sortDir) !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    <a href="{{ apSortUrl($apBase, $apParams, 'amount', $sortField, $sortDir) }}" class="hover:text-indigo-600">
                                        Betrag (&euro;){!! apSortIcon('amount', $sortField, $sortDir) !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Laufzeit</th>
                                @if($canWrite)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($positions as $pos)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">{{ $pos->project_name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs
                                            {{ $pos->priority === 'hoch' ? 'bg-red-100 text-red-800' : ($pos->priority === 'mittel' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $pos->priority }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $pos->category }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs
                                            {{ $pos->status === 'geplant' ? 'bg-blue-100 text-blue-800' : ($pos->status === 'angepasst' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-700') }}">
                                            {{ $pos->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format($pos->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @if($pos->is_recurring)
                                            <span title="Wiederkehrend">↻</span>
                                            {{ $pos->start_year ?? '–' }}@if($pos->end_year) – {{ $pos->end_year }}@endif
                                        @else
                                            {{ $pos->start_year ?? '–' }}
                                        @endif
                                    </td>
                                    @if($canWrite)
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <button onclick="openEditPos({{ json_encode([
                                                'id'                     => $pos->id,
                                                'budget_year_version_id' => $pos->budget_year_version_id,
                                                'cost_center_id'         => $pos->cost_center_id,
                                                'account_id'             => $pos->account_id,
                                                'project_name'           => $pos->project_name,
                                                'amount'                 => $pos->amount,
                                                'priority'               => $pos->priority,
                                                'category'               => $pos->category,
                                                'status'                 => $pos->status,
                                                'description'            => $pos->description,
                                                'start_year'             => $pos->start_year,
                                                'end_year'               => $pos->end_year,
                                                'is_recurring'           => $pos->is_recurring,
                                            ]) }})"
                                                    class="text-blue-600 hover:underline mr-3">Bearbeiten</button>
                                            <form method="POST" action="{{ route('hh.positions.destroy', $pos) }}"
                                                  class="inline" onsubmit="return confirm('Position wirklich loeschen?')">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="_redirect_to" value="{{ request()->fullUrl() }}">
                                                <button type="submit" class="text-red-600 hover:underline">Loeschen</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canWrite ? 7 : 6 }}" class="px-4 py-6 text-center text-gray-500">
                                        Keine Positionen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($positions->isNotEmpty())
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700">Gesamt</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        {{ number_format($positions->sum('amount'), 0, ',', '.') }} &euro;
                                    </td>
                                    <td colspan="{{ $canWrite ? 2 : 1 }}"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>
    </div>

    @if($canWrite && $versionId)
        {{-- Create Modal --}}
        <div id="modal-create-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             x-data="{ recurring: false }">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Neue Position</h3>
                <form method="POST" action="{{ route('hh.positions.store') }}">
                    @csrf
                    <input type="hidden" name="_redirect_to" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="budget_year_version_id" value="{{ $versionId }}">
                    <input type="hidden" name="cost_center_id" value="{{ $costCenter->id }}">
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                    <input type="hidden" name="is_recurring" :value="recurring ? '1' : '0'">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Projektname</label>
                            <input type="text" name="project_name" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Betrag (&euro;)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priorität</label>
                            <select name="priority" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="hoch">hoch</option>
                                <option value="mittel" selected>mittel</option>
                                <option value="niedrig">niedrig</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategorie</label>
                            <select name="category" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="Pflichtaufgabe">Pflichtaufgabe</option>
                                <option value="gesetzlich gebunden">gesetzlich gebunden</option>
                                <option value="freiwillige Leistung">freiwillige Leistung</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="geplant" selected>geplant</option>
                                <option value="angepasst">angepasst</option>
                                <option value="gestrichen">gestrichen</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Startjahr</label>
                            <input type="number" name="start_year" value="{{ $budgetYear->year }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex flex-col justify-end">
                            <button type="button" @click="recurring = !recurring"
                                    :class="recurring ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
                                    class="mt-1 inline-flex items-center gap-1.5 px-3 py-2 rounded border text-sm font-medium transition-colors">
                                <span>↻</span>
                                <span x-text="recurring ? 'Wiederkehrend' : 'Einmalig'"></span>
                            </button>
                        </div>
                        <div x-show="recurring" x-cloak>
                            <label class="block text-sm font-medium text-gray-700">Bis Jahr <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" name="end_year" placeholder="unbegrenzt" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea name="description" rows="2" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-create-pos').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">Abbrechen</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Speichern</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div id="modal-edit-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
             x-data="{ recurring: false }"
             @open-edit-pos.window="
                 recurring = !!$event.detail.is_recurring;
                 $nextTick(() => {
                     document.getElementById('ep-version').value   = $event.detail.budget_year_version_id;
                     document.getElementById('ep-cc').value        = $event.detail.cost_center_id;
                     document.getElementById('ep-account').value   = $event.detail.account_id;
                     document.getElementById('ep-name').value      = $event.detail.project_name;
                     document.getElementById('ep-amount').value    = $event.detail.amount;
                     document.getElementById('ep-priority').value  = $event.detail.priority;
                     document.getElementById('ep-category').value  = $event.detail.category;
                     document.getElementById('ep-status').value    = $event.detail.status;
                     document.getElementById('ep-start').value     = $event.detail.start_year ?? '';
                     document.getElementById('ep-end').value       = $event.detail.end_year ?? '';
                     document.getElementById('ep-desc').value      = $event.detail.description ?? '';
                     var base = '{{ rtrim(route("hh.positions.update", ["position" => "__ID__"]), "/") }}'.replace('__ID__', $event.detail.id);
                     document.getElementById('form-edit-pos').action = base;
                 });
             ">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Position bearbeiten</h3>
                <form id="form-edit-pos" method="POST" action="">
                    @csrf @method('PUT')
                    <input type="hidden" name="_redirect_to" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="budget_year_version_id" id="ep-version">
                    <input type="hidden" name="cost_center_id" id="ep-cc">
                    <input type="hidden" name="account_id" id="ep-account">
                    <input type="hidden" name="is_recurring" :value="recurring ? '1' : '0'">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Projektname</label>
                            <input type="text" name="project_name" id="ep-name" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Betrag (&euro;)</label>
                            <input type="number" name="amount" id="ep-amount" step="0.01" min="0.01" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priorität</label>
                            <select name="priority" id="ep-priority" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="hoch">hoch</option>
                                <option value="mittel">mittel</option>
                                <option value="niedrig">niedrig</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategorie</label>
                            <select name="category" id="ep-category" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="Pflichtaufgabe">Pflichtaufgabe</option>
                                <option value="gesetzlich gebunden">gesetzlich gebunden</option>
                                <option value="freiwillige Leistung">freiwillige Leistung</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="ep-status" required class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="geplant">geplant</option>
                                <option value="angepasst">angepasst</option>
                                <option value="gestrichen">gestrichen</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Startjahr</label>
                            <input type="number" name="start_year" id="ep-start" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex flex-col justify-end">
                            <button type="button" @click="recurring = !recurring"
                                    :class="recurring ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300'"
                                    class="mt-1 inline-flex items-center gap-1.5 px-3 py-2 rounded border text-sm font-medium transition-colors">
                                <span>↻</span>
                                <span x-text="recurring ? 'Wiederkehrend' : 'Einmalig'"></span>
                            </button>
                        </div>
                        <div x-show="recurring" x-cloak>
                            <label class="block text-sm font-medium text-gray-700">Bis Jahr <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="number" name="end_year" id="ep-end" placeholder="unbegrenzt" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea name="description" id="ep-desc" rows="2" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-edit-pos').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">Abbrechen</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Speichern</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function openEditPos(data) {
            window.dispatchEvent(new CustomEvent('open-edit-pos', { detail: data }));
            document.getElementById('modal-edit-pos').classList.remove('hidden');
        }
    </script>
</x-app-layout>
