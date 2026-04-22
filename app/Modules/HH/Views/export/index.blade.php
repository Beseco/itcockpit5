<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Export – Haushaltsjahr {{ $budgetYear->year ?? '' }}
        </h2>
    </x-slot>

    @include('hh::partials.nav')
    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-8 space-y-6">

                <p class="text-sm text-gray-600">
                    Exportieren Sie alle Haushaltspositionen des Haushaltsjahres
                    <strong>{{ $budgetYear->year ?? '' }}</strong>
                    inklusive Summen nach Kostenstelle, Sachkonto sowie Investiv-/Konsumtiv-Gesamtsummen.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('hh.budget-years.export.excel', $budgetYear) }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-green-700 text-white font-semibold rounded-lg hover:bg-green-800 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        Excel (XLSX) herunterladen
                    </a>

                    <a href="{{ route('hh.budget-years.export.pdf', $budgetYear) }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-red-700 text-white font-semibold rounded-lg hover:bg-red-800 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        PDF herunterladen
                    </a>
                </div>

                <p class="text-xs text-gray-400">
                    Der Export enthält nur Kostenstellen, auf die Sie Lesezugriff haben.
                </p>

                <hr class="border-gray-200">

                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">CSV-Import</h3>
                    <p class="text-sm text-gray-500 mb-3">
                        Haushaltspositionen aus einer CSV-Datei (z.B. aus Excel) in ein Haushaltsjahr importieren.
                    </p>
                    <a href="{{ route('hh.import.index') }}"
                       class="inline-flex items-center justify-center px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        CSV importieren
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
