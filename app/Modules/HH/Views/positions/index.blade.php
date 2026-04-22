@php
    $allCostCenters = \App\Modules\HH\Models\CostCenter::orderBy('number')->get();
    $allAccounts    = \App\Modules\HH\Models\Account::orderBy('number')->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('hh.budget-years.index') }}"
                   class="text-sm text-blue-600 hover:underline">&larr; Zurück</a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Positionen &mdash; Version {{ $version->version_number }}
                    ({{ $version->budgetYear->year ?? '' }})
                </h2>
            </div>
            @if($canWrite)
                <button onclick="document.getElementById('modal-create-pos').classList.remove('hidden')"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                    + Neue Position
                </button>
            @endif
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 rounded bg-green-100 px-4 py-3 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded bg-red-100 px-4 py-3 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-x-auto bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Kostenstelle</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sachkonto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Projektname</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Betrag</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Priorität</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Kategorie</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Laufzeit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Wiederkehrend</th>
                            @if($canWrite)
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Aktionen</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($positions as $pos)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $pos->costCenter->number ?? '' }} {{ $pos->costCenter->name ?? '' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $pos->account->number ?? '' }} {{ $pos->account->name ?? '' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $pos->project_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ number_format($pos->amount, 2, ',', '.') }} €
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ $pos->priority }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $pos->category }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">{{ $pos->status }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $pos->start_year ?? '–' }} – {{ $pos->end_year ?? '–' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $pos->is_recurring ? 'Ja' : 'Nein' }}
                                </td>
                                @if($canWrite)
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <button onclick="openEditPosition({{ json_encode([
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
                                                class="mr-2 text-blue-600 hover:underline">
                                            Bearbeiten
                                        </button>
                                        @if($canDelete)
                                            <form method="POST"
                                                  action="{{ route('hh.positions.destroy', $pos) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('Position wirklich löschen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Löschen</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    @if($canWrite)
        {{-- Create Modal --}}
        <div id="modal-create-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Neue Position</h3>
                <form method="POST" action="{{ route('hh.positions.store') }}">
                    @csrf
                    <input type="hidden" name="budget_year_version_id" value="{{ $version->id }}">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kostenstelle</label>
                            <select name="cost_center_id" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">– bitte wählen –</option>
                                @foreach($allCostCenters as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->number }} {{ $cc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sachkonto</label>
                            <select name="account_id" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">– bitte wählen –</option>
                                @foreach($allAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->number }} {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Projektname</label>
                            <input type="text" name="project_name" required
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Betrag (€)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" required
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priorität</label>
                            <select name="priority" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="hoch">hoch</option>
                                <option value="mittel">mittel</option>
                                <option value="niedrig">niedrig</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategorie</label>
                            <select name="category" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="Pflichtaufgabe">Pflichtaufgabe</option>
                                <option value="gesetzlich gebunden">gesetzlich gebunden</option>
                                <option value="freiwillige Leistung">freiwillige Leistung</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="geplant">geplant</option>
                                <option value="angepasst">angepasst</option>
                                <option value="gestrichen">gestrichen</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Startjahr</label>
                            <input type="number" name="start_year"
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Endjahr</label>
                            <input type="number" name="end_year"
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2 flex items-center">
                            <input type="checkbox" name="is_recurring" value="1" id="create-is-recurring"
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600">
                            <label for="create-is-recurring" class="ml-2 text-sm text-gray-700">Wiederkehrend</label>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea name="description" rows="3"
                                      class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('modal-create-pos').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">
                            Abbrechen
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div id="modal-edit-pos" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-full max-w-2xl rounded bg-white p-6 shadow-lg overflow-y-auto max-h-screen">
                <h3 class="mb-4 text-lg font-semibold">Position bearbeiten</h3>
                <form id="form-edit-pos" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="budget_year_version_id" id="edit-pos-version-id">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kostenstelle</label>
                            <select name="cost_center_id" id="edit-pos-cost-center" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">– bitte wählen –</option>
                                @foreach($allCostCenters as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->number }} {{ $cc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sachkonto</label>
                            <select name="account_id" id="edit-pos-account" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">– bitte wählen –</option>
                                @foreach($allAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->number }} {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Projektname</label>
                            <input type="text" name="project_name" id="edit-pos-project-name" required
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Betrag (€)</label>
                            <input type="number" name="amount" id="edit-pos-amount" step="0.01" min="0.01" required
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priorität</label>
                            <select name="priority" id="edit-pos-priority" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="hoch">hoch</option>
                                <option value="mittel">mittel</option>
                                <option value="niedrig">niedrig</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategorie</label>
                            <select name="category" id="edit-pos-category" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="Pflichtaufgabe">Pflichtaufgabe</option>
                                <option value="gesetzlich gebunden">gesetzlich gebunden</option>
                                <option value="freiwillige Leistung">freiwillige Leistung</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="edit-pos-status" required
                                    class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="geplant">geplant</option>
                                <option value="angepasst">angepasst</option>
                                <option value="gestrichen">gestrichen</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Startjahr</label>
                            <input type="number" name="start_year" id="edit-pos-start-year"
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Endjahr</label>
                            <input type="number" name="end_year" id="edit-pos-end-year"
                                   class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="col-span-2 flex items-center">
                            <input type="checkbox" name="is_recurring" value="1" id="edit-pos-is-recurring"
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600">
                            <label for="edit-pos-is-recurring" class="ml-2 text-sm text-gray-700">Wiederkehrend</label>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                            <textarea name="description" id="edit-pos-description" rows="3"
                                      class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button"
                                onclick="document.getElementById('modal-edit-pos').classList.add('hidden')"
                                class="px-4 py-2 text-sm text-gray-700 hover:underline">
                            Abbrechen
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                            Speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditPosition(data) {
                document.getElementById('edit-pos-version-id').value    = data.budget_year_version_id;
                document.getElementById('edit-pos-cost-center').value   = data.cost_center_id;
                document.getElementById('edit-pos-account').value       = data.account_id;
                document.getElementById('edit-pos-project-name').value  = data.project_name;
                document.getElementById('edit-pos-amount').value        = data.amount;
                document.getElementById('edit-pos-priority').value      = data.priority;
                document.getElementById('edit-pos-category').value      = data.category;
                document.getElementById('edit-pos-status').value        = data.status;
                document.getElementById('edit-pos-start-year').value    = data.start_year ?? '';
                document.getElementById('edit-pos-end-year').value      = data.end_year ?? '';
                document.getElementById('edit-pos-is-recurring').checked = !!data.is_recurring;
                document.getElementById('edit-pos-description').value   = data.description ?? '';
                document.getElementById('form-edit-pos').action         = '{{ url('hh/positions') }}/' + data.id;
                document.getElementById('modal-edit-pos').classList.remove('hidden');
            }
        </script>
    @endif

</x-app-layout>
