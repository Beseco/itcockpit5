<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Export – Haushaltsjahr {{ $budgetYear->year ?? '' }}
        </h2>
    </x-slot>

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

            </div>
        </div>
    </div>
</x-app-layout>
