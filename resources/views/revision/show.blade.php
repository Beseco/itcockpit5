<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision: {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">

<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="bg-gray-800 text-white rounded-t-lg px-6 py-4">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">IT Cockpit · Revisionsformular</div>
        <h1 class="text-xl font-bold">{{ $app->name }}</h1>
    </div>

    @if($alreadyDone)
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

            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 rounded-lg px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 1. Applikation noch in Verwendung --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">1</span>
                    Wird die Applikation noch aktiv verwendet?
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="app_aktiv" value="ja" x-model="app_aktiv"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="app_aktiv" value="nein" x-model="app_aktiv"> Nein
                    </label>
                </div>
                <div x-show="app_aktiv === 'nein'" x-transition style="display:none">
                    <textarea name="app_aktiv_notiz" rows="2"
                              placeholder="Bitte erläutern (z.B. abgelöst durch, abgeschaltet am …)"
                              class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('app_aktiv_notiz') }}</textarea>
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
                        <input type="radio" name="admin_korrekt" value="ja" x-model="admin_korrekt"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="admin_korrekt" value="nein" x-model="admin_korrekt"> Nein
                    </label>
                </div>
                <div x-show="admin_korrekt === 'nein'" x-transition style="display:none">
                    <label class="block text-sm text-gray-600 mb-1">Neuer IT-Administrator:</label>
                    <select name="new_admin_user_id"
                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">– Bitte auswählen –</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('new_admin_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Die Änderung wird direkt in der Datenbank gespeichert.</p>
                </div>
            </div>

            {{-- 3. Verfahrensverantwortlicher --}}
            <div class="border border-gray-200 rounded-lg p-4">
                <p class="font-medium text-gray-800 mb-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-xs font-bold mr-2">3</span>
                    Ist der Verfahrensverantwortliche noch korrekt und im Unternehmen tätig?
                </p>
                <p class="text-sm text-gray-500 mb-3">
                    Aktuell: <strong>{{ $app->verantwortlichAdUser?->anzeigenameOrName ?? ($app->verantwortlich_sg ?: '(nicht gesetzt)') }}</strong>
                </p>
                <div class="flex gap-6 mb-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="verantwortlich_korrekt" value="ja" x-model="verantwortlich_korrekt"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="verantwortlich_korrekt" value="nein" x-model="verantwortlich_korrekt"> Nein / Geändert
                    </label>
                </div>
                <div x-show="verantwortlich_korrekt === 'nein'" x-transition style="display:none">
                    <label class="block text-sm text-gray-600 mb-1">Neuer Verfahrensverantwortlicher:</label>
                    <select name="new_verantwortlich_ad_user_id"
                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">– Bitte auswählen –</option>
                        @foreach($adUsers as $adUser)
                            <option value="{{ $adUser->id }}" {{ old('new_verantwortlich_ad_user_id') == $adUser->id ? 'selected' : '' }}>
                                {{ $adUser->anzeigenameOrName }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        Die Person wird direkt eingetragen und per E-Mail über die Zuordnung informiert.
                    </p>
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
                        <input type="radio" name="doc_aktuell" value="ja" x-model="doc_aktuell"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="doc_aktuell" value="nein" x-model="doc_aktuell"> Nein / Neue URL
                    </label>
                </div>
                <div x-show="doc_aktuell === 'nein'" x-transition style="display:none">
                    <label class="block text-sm text-gray-600 mb-1">Neue Dokumentations-URL:</label>
                    <input type="url" name="doc_url" value="{{ old('doc_url', $app->doc_url) }}"
                           placeholder="https://docs.example.com/..."
                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Die neue URL wird direkt in der Datenbank gespeichert.</p>
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
                        <input type="radio" name="lieferant_korrekt" value="ja" x-model="lieferant_korrekt"> Ja
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="lieferant_korrekt" value="nein" x-model="lieferant_korrekt"> Nein / Geändert
                    </label>
                </div>
                <div x-show="lieferant_korrekt === 'nein'" x-transition style="display:none" class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Neuer Hersteller / Lieferant:</label>
                        <select name="new_dienstleister_id"
                                class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">– Bitte auswählen –</option>
                            @foreach($dienstleister as $dl)
                                <option value="{{ $dl->id }}" {{ old('new_dienstleister_id') == $dl->id ? 'selected' : '' }}>
                                    {{ $dl->firmenname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Ansprechpartner beim Hersteller:</label>
                        <input type="text" name="new_ansprechpartner"
                               value="{{ old('new_ansprechpartner', $app->ansprechpartner) }}"
                               placeholder="Name des Ansprechpartners"
                               class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <p class="text-xs text-gray-400">Änderungen werden direkt in der Datenbank gespeichert.</p>
                </div>
            </div>

            {{-- Allgemeine Anmerkungen --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Allgemeine Anmerkungen (optional)</label>
                <textarea name="anmerkungen" rows="3"
                          class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Weitere Hinweise, Änderungswünsche oder Anmerkungen …">{{ old('anmerkungen') }}</textarea>
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
