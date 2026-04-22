<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Haushaltsplanung – Haushaltsjahre
            </h2>
            @if($isLeiter)
                <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition">
                    + Neues Haushaltsjahr
                </button>
            @endif
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Suche --}}
            <div class="bg-white shadow rounded-lg p-3 mb-4 flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" id="by-search" placeholder="Jahr oder Status suchen…"
                       class="flex-1 border-0 text-sm focus:ring-0 outline-none text-gray-700 placeholder-gray-400">
                <span id="by-search-count" class="text-xs text-gray-400 hidden"></span>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jahr</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Versionen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($budgetYears as $by)
                        <tr data-search="{{ $by->year }} {{ $by->status }}">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $by->year }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ match($by->status) {
                                        'approved'   => 'bg-green-100 text-green-800',
                                        'preliminary'=> 'bg-yellow-100 text-yellow-800',
                                        'archiviert' => 'bg-slate-100 text-slate-500',
                                        default      => 'bg-gray-100 text-gray-800'
                                    } }}">
                                    {{ match($by->status) { 'draft' => 'Entwurf', 'preliminary' => 'Vorläufig', 'approved' => 'Genehmigt', 'archiviert' => 'Archiviert', default => $by->status } }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $by->versions->count() }}</td>
                            <td class="px-6 py-4 text-sm space-x-2 whitespace-nowrap">
                                @if($by->versions->isNotEmpty())
                                    <a href="{{ route('hh.dashboard.show', $by) }}"
                                       class="text-indigo-600 hover:underline">Dashboard</a>
                                @endif
                                <a href="{{ route('hh.budget-years.export.excel', $by) }}"
                                   class="text-green-600 hover:underline">Excel</a>
                                <a href="{{ route('hh.budget-years.export.pdf', $by) }}"
                                   class="text-red-600 hover:underline">PDF</a>
                                @if($isLeiter)
                                    @if($by->status === 'draft')
                                        <button type="button"
                                                onclick="openCarryOver({{ $by->id }}, {{ $by->year }})"
                                                class="text-teal-600 hover:underline">↻ Übertragen</button>
                                    @endif
                                    <button type="button"
                                            onclick="openEditYear({{ $by->id }}, {{ $by->year }}, '{{ $by->status }}')"
                                            class="text-blue-600 hover:underline">Bearbeiten</button>
                                    <form method="POST" action="{{ route('hh.budget-years.destroy', $by) }}"
                                          class="inline"
                                          onsubmit="return confirm('Haushaltsjahr {{ $by->year }} wirklich löschen? Dies ist nur möglich wenn keine Positionen vorhanden sind.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                Noch keine Haushaltsjahre vorhanden.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('by-search').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr[data-search]');
            let visible = 0;
            rows.forEach(function (row) {
                const show = !term || row.dataset.search.toLowerCase().includes(term);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const counter = document.getElementById('by-search-count');
            if (term) {
                counter.textContent = visible + ' Treffer';
                counter.classList.remove('hidden');
            } else {
                counter.classList.add('hidden');
            }
        });

        function openEditYear(id, year, status) {
            document.getElementById('edit-year-input').value = year;
            document.getElementById('edit-status-select').value = status;
            document.getElementById('form-edit-year').action =
                '{{ url('hh/budget-years') }}/' + id;
            document.getElementById('modal-edit').classList.remove('hidden');
        }
    </script>

    {{-- Modal: Neues Haushaltsjahr --}}
    @if($isLeiter)
    <div id="modal-create" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Neues Haushaltsjahr anlegen</h3>
            <form method="POST" action="{{ route('hh.budget-years.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                    <input type="number" name="year" min="2020" max="2099"
                           value="{{ date('Y') + 1 }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-create').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                        Anlegen
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Haushaltsjahr bearbeiten --}}
    <div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Haushaltsjahr bearbeiten</h3>
            <form id="form-edit-year" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                    <input type="number" name="year" id="edit-year-input" min="2020" max="2099"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="edit-status-select"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="draft">Entwurf</option>
                        <option value="preliminary">Vorläufig</option>
                        <option value="approved">Genehmigt</option>
                        <option value="archiviert">Archiviert</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-edit').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- Modal: Wiederkehrende Positionen übertragen --}}
    @if($isLeiter)
    <div id="modal-carry-over" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm overflow-y-auto max-h-[90vh]">
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Wiederkehrende Positionen übertragen</h3>
            <p class="text-sm text-gray-500 mb-4">
                Alle wiederkehrenden Positionen aus dem gewählten Quell-Jahr werden in
                <strong id="carry-over-target-label"></strong> übernommen.
                Bereits vorhandene Positionen werden übersprungen – der Vorgang kann mehrfach ausgeführt werden.
            </p>
            <form id="form-carry-over" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quell-Haushaltsjahr</label>
                    <select name="source_budget_year_id" id="carry-over-source"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @foreach($budgetYears as $by)
                            <option value="{{ $by->id }}" data-target-id="0">
                                {{ $by->year }}
                                ({{ match($by->status) { 'draft'=>'Entwurf','preliminary'=>'Vorläufig','approved'=>'Genehmigt','archiviert'=>'Archiviert',default=>$by->status } }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('modal-carry-over').classList.add('hidden')"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-teal-600 text-white text-sm rounded hover:bg-teal-700">
                        Übertragen
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
        function openCarryOver(targetId, targetYear) {
            document.getElementById('carry-over-target-label').textContent = 'HJ ' + targetYear;
            document.getElementById('form-carry-over').action =
                '{{ url('hh/budget-years') }}/' + targetId + '/carry-over-recurring';

            // Entferne das Ziel-Jahr aus der Quell-Auswahl
            const select = document.getElementById('carry-over-source');
            Array.from(select.options).forEach(opt => {
                opt.disabled = (parseInt(opt.value) === targetId);
            });
            // Wähle erste nicht-deaktivierte Option vor
            const first = Array.from(select.options).find(o => !o.disabled);
            if (first) select.value = first.value;

            document.getElementById('modal-carry-over').classList.remove('hidden');
        }
    </script>

@endif

</x-app-layout>
