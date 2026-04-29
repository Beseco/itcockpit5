<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applikation bearbeiten: {{ $app->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="rounded-md bg-green-50 border border-green-300 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('revision_error'))
                <div class="rounded-md bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-800">
                    {{ session('revision_error') }}
                </div>
            @endif

            {{-- Fehlende Dokumentation --}}
            @if(!$app->doc_url)
                <div class="rounded-md bg-amber-50 border border-amber-300 px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="text-sm text-amber-800">
                        <span class="font-semibold">Keine Dokumentation hinterlegt.</span>
                        Bitte schnellstmöglich eine Dokumentations-URL im Feld unten eintragen.
                    </div>
                </div>
            @endif

            {{-- Revision fällig --}}
            @if($app->revision_date)
                @if($app->revision_date->isPast())
                    <div x-data="{ open: false }" class="rounded-md bg-red-50 border border-red-300 px-4 py-3 flex items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-red-800">
                                <span class="font-semibold">Revision fällig</span> seit {{ $app->revision_date->format('d.m.Y') }}
                                ({{ abs($app->revision_date->diffInDays(now())) }} Tage überfällig)
                            </div>
                        </div>
                        @can('applikationen.edit')
                        @php
                            $revMissing = array_filter([
                                empty($app->einsatzzweck)                ? 'Beschreibung' : null,
                                empty($app->hersteller)                  ? 'Hersteller' : null,
                                empty($app->doc_url)                     ? 'Dokumentations-URL' : null,
                                empty($app->verantwortlich_ad_user_id)   ? 'Verfahrensverantwortlicher' : null,
                                empty($app->admin_user_id)               ? 'IT-Administrator' : null,
                            ]);
                            $revReady = empty($revMissing);
                        @endphp
                        <button type="button" @click="open = true"
                                class="shrink-0 inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                            Revision durchführen
                        </button>

                        {{-- Bestätigungs-Modal --}}
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition>
                            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                                <h3 class="text-base font-semibold text-gray-900 mb-3">Revision bestätigen</h3>

                                @if(!$revReady)
                                    <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 mb-4">
                                        <p class="text-sm font-semibold text-red-700 mb-2">Folgende Felder müssen vor der Revision ausgefüllt sein:</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach($revMissing as $field)
                                                <li class="text-sm text-red-600">{{ $field }}</li>
                                            @endforeach
                                        </ul>
                                        <p class="text-xs text-red-500 mt-2">Bitte das Formular unten speichern und dann erneut versuchen.</p>
                                    </div>
                                    <div class="flex justify-end">
                                        <button @click="open = false" type="button"
                                                class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Schließen</button>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600 mb-1">
                                        Hiermit bestätige ich, dass die Revision der Applikation
                                        <strong>{{ $app->name }}</strong> durchgeführt wurde.
                                    </p>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Das Revisionsdatum wird auf <strong>{{ now()->addYear()->format('d.m.Y') }}</strong> gesetzt.
                                    </p>
                                    <div class="flex justify-end gap-3">
                                        <button @click="open = false" type="button"
                                                class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">Abbrechen</button>
                                        <form action="{{ route('applikationen.revision-done', $app) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md font-semibold">
                                                Ja, Revision durchgeführt
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endcan
                    </div>
                @else
                    <div class="rounded-md bg-gray-50 border border-gray-200 px-4 py-3 flex items-center gap-3 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Nächste Revision: <span class="font-medium text-gray-800">{{ $app->revision_date->format('d.m.Y') }}</span>
                        <span class="text-gray-400">(in {{ $app->revision_date->diffInDays(now()) }} Tagen)</span>
                    </div>
                @endif
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('applikationen.update', $app) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('applikationen._form')
                        <div class="flex items-center justify-between mt-6">
                            <div>
                                @can('applikationen.delete')
                                    {{-- Delete-Formular AUSSERHALB des Update-Formulars via JS-Submit --}}
                                    <button type="button"
                                            onclick="if(confirm('Applikation wirklich löschen?')) document.getElementById('delete-form-{{ $app->id }}').submit()"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        Löschen
                                    </button>
                                @endcan
                            </div>
                            <div class="flex gap-3">
                                <a href="{{ route('applikationen.index') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    Abbrechen
                                </a>
                                <x-primary-button>Speichern</x-primary-button>
                            </div>
                        </div>
                    </form>

                    @can('applikationen.delete')
                    <form id="delete-form-{{ $app->id }}"
                          action="{{ route('applikationen.destroy', $app) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
