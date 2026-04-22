<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                CSV-Import – Haushaltspositionen
            </h2>
            <a href="{{ route('hh.dashboard.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                ← Zurück zum Dashboard
            </a>
        </div>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Result box --}}
            @if(isset($result))
                @if(empty($result['errors']) && $result['imported'] > 0)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-semibold text-green-800 mb-1">Import erfolgreich – Haushaltsjahr {{ $importedYear }}</h3>
                        <ul class="text-sm text-green-700 space-y-0.5">
                            <li>✓ {{ $result['imported'] }} Position(en) importiert</li>
                            @if($result['skipped'] > 0)
                                <li class="text-gray-500">↷ {{ $result['skipped'] }} Zeile(n) übersprungen (Betrag = 0 oder leer)</li>
                            @endif
                        </ul>
                        @if(!empty($result['warnings']))
                            <div class="mt-3 pt-3 border-t border-green-200">
                                <p class="text-xs font-semibold text-green-700 mb-1">Hinweise:</p>
                                <ul class="text-xs text-green-600 space-y-0.5">
                                    @foreach($result['warnings'] as $w)
                                        <li>ℹ {{ $w }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="font-semibold text-red-800 mb-1">Import fehlgeschlagen</h3>
                        <ul class="text-sm text-red-700 space-y-0.5">
                            @foreach($result['errors'] as $e)
                                <li>✗ {{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif

            {{-- Errors from validation --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <ul class="text-sm text-red-700 space-y-0.5">
                        @foreach($errors->all() as $e)
                            <li>✗ {{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Import form --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">CSV-Datei importieren</h3>

                <form method="POST" action="{{ route('hh.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    {{-- Budget year selector --}}
                    <div>
                        <label for="budget_year_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Ziel-Haushaltsjahr <span class="text-red-500">*</span>
                        </label>
                        @if($budgetYears->isEmpty())
                            <p class="text-sm text-red-600">
                                Es sind noch keine Haushaltsjahre angelegt.
                                <a href="{{ route('hh.budget-years.index') }}" class="underline">Jetzt anlegen</a>
                            </p>
                        @else
                            <select name="budget_year_id" id="budget_year_id"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                @foreach($budgetYears as $by)
                                    <option value="{{ $by->id }}"
                                        {{ (isset($selectedYearId) && $selectedYearId == $by->id) ? 'selected' : '' }}
                                        {{ $by->status === 'approved' ? 'disabled' : '' }}>
                                        {{ $by->year }}
                                        ({{ ucfirst($by->status) }})
                                        {{ $by->status === 'approved' ? '– gesperrt' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- File upload --}}
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">
                            CSV-Datei <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-1" />
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit" @if($budgetYears->isEmpty()) disabled @endif
                                class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Importieren
                        </button>
                        @if(isset($result) && $result['imported'] > 0)
                            <a href="{{ route('hh.versions.positions.index', \App\Modules\HH\Models\BudgetYearVersion::where('budget_year_id', $selectedYearId)->where('is_active', true)->first()?->id ?? 0) }}"
                               class="text-sm text-indigo-600 hover:underline">
                                Importierte Positionen ansehen →
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Format description --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Erwartetes CSV-Format</h4>
                <p class="text-xs text-gray-500 mb-3">
                    Trennzeichen: Semikolon <code class="bg-gray-100 px-1 rounded">;</code> &nbsp;|&nbsp;
                    Kodierung: UTF-8 oder Windows-1252 (wird automatisch erkannt) &nbsp;|&nbsp;
                    Zeilen mit Betrag 0 werden übersprungen
                </p>
                <div class="overflow-x-auto">
                    <table class="text-xs text-gray-600 border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-2 py-1 text-left">Spalte</th>
                                <th class="border border-gray-300 px-2 py-1 text-left">Beschreibung</th>
                                <th class="border border-gray-300 px-2 py-1 text-left">Beispiel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">kostenstelle</td><td class="border border-gray-300 px-2 py-1">Kostenstellen-Nr.</td><td class="border border-gray-300 px-2 py-1">143011</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">sachkonto</td><td class="border border-gray-300 px-2 py-1">Sachkonto-Nr.</td><td class="border border-gray-300 px-2 py-1">01210002</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">sachkontoname</td><td class="border border-gray-300 px-2 py-1">Name des Sachkontos</td><td class="border border-gray-300 px-2 py-1">Software</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">name</td><td class="border border-gray-300 px-2 py-1">Bezeichnung der Position</td><td class="border border-gray-300 px-2 py-1">Erweiterung Baramundi</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">beschreibung</td><td class="border border-gray-300 px-2 py-1">Optionale Beschreibung</td><td class="border border-gray-300 px-2 py-1">Lizenzkosten…</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">hhjahr</td><td class="border border-gray-300 px-2 py-1">Jahr oder „jährlich"</td><td class="border border-gray-300 px-2 py-1">2026 / jährlich</td></tr>
                            <tr><td class="border border-gray-300 px-2 py-1 font-mono">brutto</td><td class="border border-gray-300 px-2 py-1">Betrag (dt. Format)</td><td class="border border-gray-300 px-2 py-1">55.000,00 €</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Sachkonten, die mit <code class="bg-gray-100 px-1 rounded">0</code> beginnen (z.B. 01210002, 08222102), werden als <strong>investiv</strong> eingestuft. Alle übrigen als <strong>konsumtiv</strong>.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
