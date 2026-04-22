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

            {{-- Live-Suche --}}
            <div class="bg-white shadow rounded-lg p-3 mb-4 flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" id="pos-search" placeholder="Positionsname suchen…"
                       class="flex-1 border-0 text-sm focus:ring-0 outline-none text-gray-700 placeholder-gray-400">
                <span id="pos-search-count" class="text-xs text-gray-400 hidden"></span>
            </div>

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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wiederk.</th>
                                @if($canWrite)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($positions as $pos)
                                <tr class="hover:bg-gray-50" data-name="{{ $pos->project_name }}">
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
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format($pos->amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $pos->start_year ?? '&ndash;' }} &ndash; {{ $pos->end_year ?? '&ndash;' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $pos->is_recurring ? 'Ja' : 'Nein' }}</td>
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
                                                <input type="hidden" name="_redirect_to" value="{{ url()->current() }}">
                                                <button type="submit" class="text-red-600 hover:underline">Loeschen</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canWrite ? 8 : 7 }}" class="px-4 py-6 text-center text-gray-500">
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
                                        {{ number_format($positions->sum('amount'), 2, ',', '.') }} &euro;
                                    </td>
                                    <td colspan="{{ $canWrite ? 3 : 2 }}"></td>
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
        <div id="modal-create-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Neue Position</h3>
                <form method="POST" action="{{ route('hh.positions.store') }}">
                    @csrf
                    <input type="hidden" name="_redirect_to" value="{{ url()->current() }}">
                    <input type="hidden" name="budget_year_version_id" value="{{ $versionId }}">
                    <input type="hidden" name="cost_center_id" value="{{ $costCenter->id }}">
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
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
                            <label class="block text-sm font-medium text-gray-700">Prioritaet</label>
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
                            <input type="number" name="start_year" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Endjahr</label>
                            <input type="number" name="end_year" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" name="is_recurring" value="1" id="cr-recurring" class="rounded border-gray-300">
                            <label for="cr-recurring" class="text-sm text-gray-700">Wiederkehrend</label>
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
        <div id="modal-edit-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Position bearbeiten</h3>
                <form id="form-edit-pos" method="POST" action="">
                    @csrf @method('PUT')
                    <input type="hidden" name="_redirect_to" value="{{ url()->current() }}">
                    <input type="hidden" name="budget_year_version_id" id="ep-version">
                    <input type="hidden" name="cost_center_id" id="ep-cc">
                    <input type="hidden" name="account_id" id="ep-account">
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
                            <label class="block text-sm font-medium text-gray-700">Prioritaet</label>
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Endjahr</label>
                            <input type="number" name="end_year" id="ep-end" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" name="is_recurring" value="1" id="ep-recurring" class="rounded border-gray-300">
                            <label for="ep-recurring" class="text-sm text-gray-700">Wiederkehrend</label>
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
        // Live-Suche
        document.getElementById('pos-search').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr[data-name]');
            let visible = 0;
            rows.forEach(function (row) {
                const name = row.dataset.name.toLowerCase();
                const show = !term || name.includes(term);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const counter = document.getElementById('pos-search-count');
            if (term) {
                counter.textContent = visible + ' Treffer';
                counter.classList.remove('hidden');
            } else {
                counter.classList.add('hidden');
            }
        });

        function openEditPos(data) {
            document.getElementById('ep-version').value   = data.budget_year_version_id;
            document.getElementById('ep-cc').value        = data.cost_center_id;
            document.getElementById('ep-account').value   = data.account_id;
            document.getElementById('ep-name').value      = data.project_name;
            document.getElementById('ep-amount').value    = data.amount;
            document.getElementById('ep-priority').value  = data.priority;
            document.getElementById('ep-category').value  = data.category;
            document.getElementById('ep-status').value    = data.status;
            document.getElementById('ep-start').value     = data.start_year ?? '';
            document.getElementById('ep-end').value       = data.end_year ?? '';
            document.getElementById('ep-recurring').checked = !!data.is_recurring;
            document.getElementById('ep-desc').value      = data.description ?? '';
            var posBase = '{{ rtrim(route("hh.positions.update", ["position" => "__ID__"]), "/") }}'.replace('__ID__', data.id);
            document.getElementById('form-edit-pos').action = posBase;
            document.getElementById('modal-edit-pos').classList.remove('hidden');
        }
    </script>
</x-app-layout>
