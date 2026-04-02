<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('dienstleister.index') }}"
                   class="text-gray-400 hover:text-gray-600 text-sm">Firmen & Dienstleister</a>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    {{ $dienstleister->firmenname }}
                    @if ($dienstleister->kritischer_dienstleister)
                        <span class="px-1.5 py-0.5 text-xs font-bold bg-red-100 text-red-700 rounded" title="Kritischer Dienstleister">!</span>
                    @endif
                    @if ($dienstleister->empfehlung)
                        <span class="text-green-500 text-base" title="Empfohlen">👍</span>
                    @endif
                </h2>
            </div>
            <div class="flex items-center gap-2">
                @can('dienstleister.edit')
                <a href="{{ route('dienstleister.edit', $dienstleister) }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 gap-1">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Bearbeiten
                </a>
                @endcan
                <a href="{{ route('dienstleister.index') }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200">
                    Zurück zur Liste
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Hauptinfo --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Linke Spalte: Stammdaten --}}
                <div class="space-y-6">

                    {{-- Adresse & Kontakt --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Adresse & Kontakt</h3>
                        <dl class="space-y-3">
                            @if ($dienstleister->strasse || $dienstleister->ort)
                            <div>
                                <dt class="text-xs text-gray-400">Adresse</dt>
                                <dd class="text-sm text-gray-800">
                                    {{ $dienstleister->strasse }}<br>
                                    {{ trim(($dienstleister->plz ?? '') . ' ' . ($dienstleister->ort ?? '')) }}
                                    @if ($dienstleister->land && $dienstleister->land !== 'Deutschland')
                                        <br>{{ $dienstleister->land }}
                                    @endif
                                </dd>
                            </div>
                            @endif
                            @if ($dienstleister->telefon)
                            <div>
                                <dt class="text-xs text-gray-400">Telefon</dt>
                                <dd class="text-sm text-gray-800">{{ $dienstleister->telefon }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->email)
                            <div>
                                <dt class="text-xs text-gray-400">E-Mail</dt>
                                <dd class="text-sm">
                                    <a href="mailto:{{ $dienstleister->email }}" class="text-indigo-600 hover:underline">{{ $dienstleister->email }}</a>
                                </dd>
                            </div>
                            @endif
                            @if ($dienstleister->website)
                            <div>
                                <dt class="text-xs text-gray-400">Website</dt>
                                <dd class="text-sm">
                                    <a href="{{ $dienstleister->website }}" target="_blank" class="text-indigo-600 hover:underline">{{ $dienstleister->website }}</a>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Dienstleistung --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Dienstleistung</h3>
                        <dl class="space-y-3">
                            @if ($dienstleister->dienstleister_typ)
                            <div>
                                <dt class="text-xs text-gray-400">Typ</dt>
                                <dd class="text-sm text-gray-800">{{ \App\Models\Dienstleister::TYPEN[$dienstleister->dienstleister_typ] ?? $dienstleister->dienstleister_typ }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->fachgebiet)
                            <div>
                                <dt class="text-xs text-gray-400">Fachgebiet</dt>
                                <dd class="text-sm text-gray-800">{{ $dienstleister->fachgebiet }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->leistungsbeschreibung)
                            <div>
                                <dt class="text-xs text-gray-400">Leistungsbeschreibung</dt>
                                <dd class="text-sm text-gray-700 whitespace-pre-wrap">{{ $dienstleister->leistungsbeschreibung }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->verantwortliche_stelle)
                            <div>
                                <dt class="text-xs text-gray-400">Verantwortliche Stelle</dt>
                                <dd class="text-sm text-gray-800">{{ $dienstleister->verantwortliche_stelle }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    @if ($dienstleister->bemerkungen)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Bemerkungen</h3>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $dienstleister->bemerkungen }}</p>
                    </div>
                    @endif
                </div>

                {{-- Rechte Spalte: Status, Bewertung, DSGVO --}}
                <div class="space-y-6">

                    {{-- Status & Bewertung --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Status & Bewertung</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs text-gray-400">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $dienstleister->status === 'aktiv' ? 'bg-green-100 text-green-800' :
                                           ($dienstleister->status === 'gesperrt' ? 'bg-red-100 text-red-800' :
                                           ($dienstleister->status === 'potenziell' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700')) }}">
                                        {{ \App\Models\Dienstleister::STATUS[$dienstleister->status] ?? $dienstleister->status }}
                                    </span>
                                </dd>
                            </div>
                            @if ($dienstleister->bewertung_gesamt)
                            <div>
                                <dt class="text-xs text-gray-400">Gesamtbewertung</dt>
                                <dd class="flex gap-0.5 text-lg leading-none mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        @if ($i <= $dienstleister->bewertung_gesamt)
                                            <span class="text-yellow-400">★</span>
                                        @else
                                            <span class="text-gray-200">☆</span>
                                        @endif
                                    @endfor
                                </dd>
                            </div>
                            @endif
                            @if ($dienstleister->bewertung_fachlich)
                            <div>
                                <dt class="text-xs text-gray-400">Fachliche Bewertung</dt>
                                <dd class="flex gap-0.5 text-base leading-none mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $dienstleister->bewertung_fachlich ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                                    @endfor
                                </dd>
                            </div>
                            @endif
                            @if ($dienstleister->bewertung_zuverlaessigkeit)
                            <div>
                                <dt class="text-xs text-gray-400">Zuverlässigkeit</dt>
                                <dd class="flex gap-0.5 text-base leading-none mt-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $dienstleister->bewertung_zuverlaessigkeit ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                                    @endfor
                                </dd>
                            </div>
                            @endif
                            @if ($dienstleister->bewertungsnotiz)
                            <div>
                                <dt class="text-xs text-gray-400">Bewertungsnotiz</dt>
                                <dd class="text-sm text-gray-700">{{ $dienstleister->bewertungsnotiz }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    {{-- DSGVO --}}
                    @if ($dienstleister->verarbeitet_personenbezogene_daten)
                    <div class="bg-yellow-50 border border-yellow-200 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Datenschutz (DSGVO)</h3>
                        <dl class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-yellow-600">⚠</span>
                                <span class="text-sm text-gray-700">Verarbeitet personenbezogene Daten</span>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">AV-Vertrag</dt>
                                <dd class="mt-1">
                                    @if ($dienstleister->av_vertrag_vorhanden)
                                        <span class="px-2 text-xs rounded-full bg-indigo-100 text-indigo-700">AV-Vertrag vorhanden ✓</span>
                                    @else
                                        <span class="px-2 text-xs rounded-full bg-red-100 text-red-700">AV-Vertrag fehlt!</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($dienstleister->av_vertrag_datum)
                            <div>
                                <dt class="text-xs text-gray-400">Vertragsdatum</dt>
                                <dd class="text-sm text-gray-800">{{ $dienstleister->av_vertrag_datum->format('d.m.Y') }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->av_bemerkungen)
                            <div>
                                <dt class="text-xs text-gray-400">Ablageort / Bemerkung</dt>
                                <dd class="text-sm text-gray-700">{{ $dienstleister->av_bemerkungen }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                    @endif

                    {{-- Meta --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Metadaten</h3>
                        <dl class="space-y-2 text-sm">
                            @if ($dienstleister->angelegt_am)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Angelegt am</dt>
                                <dd class="text-gray-700">{{ $dienstleister->angelegt_am->format('d.m.Y') }}</dd>
                            </div>
                            @endif
                            @if ($dienstleister->aktualisiert_am)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Zuletzt geändert</dt>
                                <dd class="text-gray-700">{{ $dienstleister->aktualisiert_am->format('d.m.Y') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Ansprechpartner --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                            Ansprechpartner
                            @if ($dienstleister->kontakte->isNotEmpty())
                                <span class="ml-1 text-gray-400 font-normal normal-case">({{ $dienstleister->kontakte->count() }})</span>
                            @endif
                        </h3>
                        @can('dienstleister.edit')
                        <a href="{{ route('dienstleister.edit', $dienstleister) }}#ansprechpartner"
                           class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Bearbeiten
                        </a>
                        @endcan
                    </div>

                    @if ($dienstleister->kontakte->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-4">Noch keine Ansprechpartner angelegt.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($dienstleister->kontakte as $ap)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">
                                            @if ($ap->anrede) {{ $ap->anrede }} @endif
                                            {{ $ap->vorname }} {{ $ap->nachname }}
                                        </p>
                                        @if ($ap->funktion)
                                            <span class="text-xs text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $ap->funktion }}</span>
                                        @endif
                                    </div>
                                </div>
                                <dl class="space-y-1 mt-3">
                                    @if ($ap->telefon)
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <span class="text-gray-400 w-4">📞</span>
                                        <span>{{ $ap->telefon }}</span>
                                    </div>
                                    @endif
                                    @if ($ap->handy)
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <span class="text-gray-400 w-4">📱</span>
                                        <span>{{ $ap->handy }}</span>
                                    </div>
                                    @endif
                                    @if ($ap->email)
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-400 w-4">✉️</span>
                                        <a href="mailto:{{ $ap->email }}" class="text-indigo-600 hover:underline">{{ $ap->email }}</a>
                                    </div>
                                    @endif
                                    @if ($ap->notiz)
                                    <div class="mt-2 pt-2 border-t border-gray-100 text-xs text-gray-500 whitespace-pre-wrap">{{ $ap->notiz }}</div>
                                    @endif
                                </dl>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
