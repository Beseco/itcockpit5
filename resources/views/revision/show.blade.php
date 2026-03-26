<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision: {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {} } }
    </script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">

<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="bg-gray-800 text-white rounded-t-lg px-6 py-4">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">IT Cockpit · Revisionsformular</div>
        <h1 class="text-xl font-bold">{{ $app->name }}</h1>
    </div>

    @if($alreadyDone)
        {{-- Bereits abgeschlossen --}}
        <div class="bg-white rounded-b-lg shadow px-6 py-10 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Revision bereits abgeschlossen</h2>
            <p class="text-gray-500">Diese Revision wurde bereits am
                <strong>{{ $app->revision_completed_at->format('d.m.Y \u\m H:i') }} Uhr</strong> abgeschlossen.
            </p>
            <p class="text-gray-400 text-sm mt-3">Nächste Revision fällig: {{ $app->revision_date->format('d.m.Y') }}</p>
        </div>
    @else
        {{-- Aktuelle App-Daten --}}
        <div class="bg-white shadow px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Aktuelle Stammdaten</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm">
                <div><span class="text-gray-400">Administrator:</span>
                    <span class="ml-2 font-medium">{{ $app->adminUser?->name ?? '–' }}</span></div>
                <div><span class="text-gray-400">Verfahrensverantwortlicher:</span>
                    <span class="ml-2 font-medium">
                        {{ $app->verantwortlichAdUser?->anzeigenameOrName ?? ($app->verantwortlich_sg ?: '–') }}
                    </span></div>
                <div><span class="text-gray-400">Sachgebiet:</span>
                    <span class="ml-2 font-medium">{{ $app->abteilung?->anzeigename ?? ($app->sg ?: '–') }}</span></div>
                <div><span class="text-gray-400">Hersteller:</span>
                    <span class="ml-2 font-medium">{{ $app->hersteller ?: '–' }}</span></div>
                <div><span class="text-gray-400">Ansprechpartner:</span>
                    <span class="ml-2 font-medium">{{ $app->ansprechpartner ?: '–' }}</span></div>
                <div><span class="text-gray-400">Revisionsdatum:</span>
                    <span class="ml-2 font-medium">{{ $app->revision_date->format('d.m.Y') }}</span></div>
                @if($app->doc_url)
                <div class="sm:col-span-2"><span class="text-gray-400">Dokumentation:</span>
                    <a href="{{ $app->doc_url }}" target="_blank"
                       class="ml-2 text-indigo-600 hover:underline break-all">{{ $app->doc_url }}</a></div>
                @endif
            </div>
        </div>

        {{-- Formular --}}
        <form action="{{ route('revision.submit', $app->revision_token) }}" method="POST"
              class="bg-white rounded-b-lg shadow px-6 py-6 space-y-6"
              x-data="{
                  app_aktiv: 'ja',
                  admin_korrekt: 'ja',
                  verantwortlich_korrekt: 'ja',
                  doc_aktuell: 'ja',
                  lieferant_korrekt: 'ja'
              }">
            @csrf

            {{-- 1. Applikation noch in Verwendung --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">1</span>
                    Wird die Applikation noch aktiv verwendet?
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="app_aktiv" value="ja" x-model="app_aktiv" class="text-indigo-600"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="app_aktiv" value="nein" x-model="app_aktiv" class="text-red-600"> Nein
                    </label>
                </div>
                <div x-show="app_aktiv === 'nein'" x-transition>
                    <textarea name="app_aktiv_notiz" rows="2" placeholder="Bitte erläutern (z.B. abgelöst durch, abgeschaltet am …)"
                              class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>

            {{-- 2. Admin noch zuständig --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">2</span>
                    Sind Sie noch der zuständige IT-Administrator für diese Applikation?
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="admin_korrekt" value="ja" x-model="admin_korrekt" class="text-indigo-600"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="admin_korrekt" value="nein" x-model="admin_korrekt" class="text-red-600"> Nein
                    </label>
                </div>
                <div x-show="admin_korrekt === 'nein'" x-transition>
                    <textarea name="admin_notiz" rows="2" placeholder="Bitte neuen zuständigen Administrator nennen"
                              class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>

            {{-- 3. Verfahrensverantwortlicher --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">3</span>
                    Ist der Verfahrensverantwortliche noch korrekt und im Unternehmen tätig?
                </p>
                <p class="text-sm text-gray-500 mb-3">
                    Aktuell:
                    <strong>{{ $app->verantwortlichAdUser?->anzeigenameOrName ?? ($app->verantwortlich_sg ?: '(nicht gesetzt)') }}</strong>
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="verantwortlich_korrekt" value="ja" x-model="verantwortlich_korrekt" class="text-indigo-600"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="verantwortlich_korrekt" value="nein" x-model="verantwortlich_korrekt" class="text-red-600"> Nein / Geändert
                    </label>
                </div>
                <div x-show="verantwortlich_korrekt === 'nein'" x-transition>
                    <textarea name="verantwortlich_notiz" rows="2" placeholder="Bitte neuen Verfahrensverantwortlichen nennen oder Änderung beschreiben"
                              class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>

            {{-- 4. Dokumentation --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">4</span>
                    Ist die Dokumentation noch aktuell?
                </p>
                @if($app->doc_url)
                <p class="text-sm text-gray-500 mb-3">
                    Aktuelle URL:
                    <a href="{{ $app->doc_url }}" target="_blank" class="text-indigo-600 hover:underline break-all">{{ $app->doc_url }}</a>
                </p>
                @else
                <p class="text-sm text-gray-400 mb-3">Derzeit keine Dokumentations-URL hinterlegt.</p>
                @endif
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="doc_aktuell" value="ja" x-model="doc_aktuell" class="text-indigo-600"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="doc_aktuell" value="nein" x-model="doc_aktuell" class="text-red-600"> Nein / Neue URL
                    </label>
                </div>
                <div x-show="doc_aktuell === 'nein'" x-transition>
                    <input type="url" name="doc_url" value="{{ $app->doc_url }}"
                           placeholder="https://docs.example.com/..."
                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Die neue URL wird direkt gespeichert.</p>
                </div>
            </div>

            {{-- 5. Lieferanteninformationen --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">5</span>
                    Stimmen die Hersteller- und Lieferanteninformationen noch?
                </p>
                <p class="text-sm text-gray-500 mb-3">
                    Hersteller: <strong>{{ $app->hersteller ?: '(nicht gesetzt)' }}</strong>
                    @if($app->ansprechpartner)
                        · Ansprechpartner: <strong>{{ $app->ansprechpartner }}</strong>
                    @endif
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="lieferant_korrekt" value="ja" x-model="lieferant_korrekt" class="text-indigo-600"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="lieferant_korrekt" value="nein" x-model="lieferant_korrekt" class="text-red-600"> Nein / Geändert
                    </label>
                </div>
                <div x-show="lieferant_korrekt === 'nein'" x-transition>
                    <textarea name="lieferant_notiz" rows="2" placeholder="Bitte Änderung beschreiben (neuer Hersteller, neuer Ansprechpartner …)"
                              class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>

            {{-- Allgemeine Anmerkungen --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Allgemeine Anmerkungen (optional)</label>
                <textarea name="anmerkungen" rows="3"
                          class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Weitere Hinweise, Änderungswünsche oder Anmerkungen …"></textarea>
            </div>

            {{-- Absenden --}}
            <div class="pt-2">
                <button type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                    Revision abschließen →
                </button>
                <p class="text-xs text-gray-400 text-center mt-2">
                    Nach dem Absenden ist dieser Link nicht mehr gültig. Die nächste Revision ist in einem Jahr fällig.
                </p>
            </div>
        </form>
    @endif
</div>

<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
