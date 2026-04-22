<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Änderungsprotokoll (Audit Trail)
        </h2>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter-Formular --}}
            <div class="bg-white shadow rounded-lg p-6">
                <form method="GET" action="{{ route('hh.audit.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Haushaltsjahr</label>
                        <select name="budget_year_id"
                                class="w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">– Alle –</option>
                            @foreach($budgetYears ?? [] as $by)
                                <option value="{{ $by->id }}"
                                    {{ request('budget_year_id') == $by->id ? 'selected' : '' }}>
                                    {{ $by->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kostenstelle</label>
                        <select name="cost_center_id"
                                class="w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">– Alle –</option>
                            @foreach($costCenters ?? [] as $cc)
                                <option value="{{ $cc->id }}"
                                    {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->number }} – {{ $cc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Von</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                               class="w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bis</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                               class="w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition">
                            Filtern
                        </button>
                        <a href="{{ route('hh.audit.index') }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded hover:bg-gray-300 transition">
                            Zurücksetzen
                        </a>
                    </div>
                </form>
            </div>

            {{-- Audit-Tabelle --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitpunkt</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entität</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feld</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alter Wert</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Neuer Wert</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($entries ?? [] as $entry)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                        {{ $entry->created_at->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $entry->user->name ?? $entry->user_id }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $entry->entity_type }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $entry->entity_id }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $entry->field }}</td>
                                    <td class="px-4 py-3 max-w-xs truncate text-gray-500">
                                        {{ $entry->old_value ?? '–' }}
                                    </td>
                                    <td class="px-4 py-3 max-w-xs truncate">
                                        {{ $entry->new_value ?? '–' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                        Keine Einträge gefunden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @isset($entries)
                    @if(method_exists($entries, 'links'))
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $entries->links() }}
                        </div>
                    @endif
                @endisset
            </div>

        </div>
    </div>
</x-app-layout>
