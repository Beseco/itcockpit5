<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Haushaltsjahr löschen
        </h2>
    </x-slot>

    @include('hh::partials.nav')

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            {{-- Warnung --}}
            <div class="bg-red-50 border border-red-300 rounded-lg p-6 mb-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800 mb-2">
                            Achtung: Diese Aktion kann nicht rückgängig gemacht werden!
                        </h3>
                        <p class="text-sm text-red-700 mb-4">
                            Folgende Daten werden unwiderruflich gelöscht:
                        </p>
                        <ul class="text-sm text-red-700 space-y-1 list-none">
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                Haushaltsjahr <strong>{{ $budgetYear->year }}</strong>
                                (Status:
                                {{ match($budgetYear->status) {
                                    'draft'       => 'Entwurf',
                                    'preliminary' => 'Vorläufig',
                                    'approved'    => 'Genehmigt',
                                    'archiviert'  => 'Archiviert',
                                    default       => $budgetYear->status
                                } }})
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <strong>{{ $versionCount }}</strong>
                                {{ $versionCount === 1 ? 'Version' : 'Versionen' }}
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <strong>{{ $positionCount }}</strong>
                                {{ $positionCount === 1 ? 'Budget-Position' : 'Budget-Positionen' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Formular mit Bestätigungswort --}}
            <div class="bg-white shadow rounded-lg p-6"
                 x-data="{ confirmation: '', get canSubmit() { return this.confirmation === 'LÖSCHEN'; } }">

                <form method="POST" action="{{ route('hh.budget-years.destroy', $budgetYear) }}"
                      @submit.prevent="if (canSubmit) $el.submit()">
                    @csrf
                    @method('DELETE')

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Gib <strong class="text-red-700 font-mono">LÖSCHEN</strong> ein, um zu bestätigen:
                    </label>
                    <input type="text"
                           name="confirmation"
                           x-model="confirmation"
                           autocomplete="off"
                           placeholder="LÖSCHEN"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 mb-5 font-mono tracking-widest uppercase">

                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('hh.budget-years.index') }}"
                           class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                            ← Abbrechen
                        </a>
                        <button type="submit"
                                :disabled="!canSubmit"
                                :class="canSubmit
                                    ? 'bg-red-600 hover:bg-red-700 text-white cursor-pointer'
                                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                class="px-5 py-2 text-sm font-medium rounded transition">
                            Haushaltsjahr {{ $budgetYear->year }} endgültig löschen
                        </button>
                    </div>
                </form>

                @if(session('error'))
                    <div class="mt-4 px-4 py-3 bg-red-100 text-red-800 rounded text-sm">{{ session('error') }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
