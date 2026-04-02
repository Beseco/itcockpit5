{{-- Gemeinsames Formular-Partial für create und edit --}}

<div x-data="{
    ansprechpartner: {{ Js::from(
        old('ansprechpartner') ?? (
            isset($dienstleister) && $dienstleister
                ? $dienstleister->ansprechpartner->map(fn($a) => [
                    'anrede'   => $a->anrede   ?? '',
                    'vorname'  => $a->vorname  ?? '',
                    'nachname' => $a->nachname ?? '',
                    'funktion' => $a->funktion ?? '',
                    'telefon'  => $a->telefon  ?? '',
                    'handy'    => $a->handy    ?? '',
                    'email'    => $a->email    ?? '',
                    'notiz'    => $a->notiz    ?? '',
                ])->values()->toArray()
                : []
        )
    ) }},
    funktionen: {{ Js::from($funktionen->pluck('name')->values()) }},
    neuerFunktionName: '',
    showNeuerFunktion: false,
    addAP() {
        this.ansprechpartner.push({ anrede: '', vorname: '', nachname: '', funktion: '', telefon: '', handy: '', email: '', notiz: '' });
    },
    removeAP(i) {
        this.ansprechpartner.splice(i, 1);
    },
    async saveFunktion() {
        const name = this.neuerFunktionName.trim();
        if (!name) return;
        const resp = await fetch('{{ route('dienstleister-funktionen.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name })
        });
        if (resp.ok) {
            this.funktionen.push(name);
            this.neuerFunktionName = '';
            this.showNeuerFunktion = false;
        }
    }
}">

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

    {{-- LINKE SPALTE --}}
    <div class="space-y-6">

        {{-- Adress & Kontaktdaten --}}
        <div class="bg-gray-50 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Adress- & Kontaktdaten</h3>
            <div class="space-y-4">

                <div>
                    <x-input-label for="firmenname" value="Firmenname *" />
                    <x-text-input id="firmenname" name="firmenname" type="text" class="mt-1 block w-full"
                                  value="{{ old('firmenname', $dienstleister->firmenname ?? '') }}" required />
                    <x-input-error :messages="$errors->get('firmenname')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="strasse" value="Straße" />
                    <x-text-input id="strasse" name="strasse" type="text" class="mt-1 block w-full"
                                  value="{{ old('strasse', $dienstleister->strasse ?? '') }}" />
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="plz" value="PLZ" />
                        <x-text-input id="plz" name="plz" type="text" class="mt-1 block w-full"
                                      value="{{ old('plz', $dienstleister->plz ?? '') }}" />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="ort" value="Ort" />
                        <x-text-input id="ort" name="ort" type="text" class="mt-1 block w-full"
                                      value="{{ old('ort', $dienstleister->ort ?? '') }}" />
                    </div>
                </div>

                <div>
                    <x-input-label for="land" value="Land" />
                    <x-text-input id="land" name="land" type="text" class="mt-1 block w-full"
                                  value="{{ old('land', $dienstleister->land ?? 'Deutschland') }}" />
                </div>

                <div>
                    <x-input-label for="telefon" value="Telefon" />
                    <x-text-input id="telefon" name="telefon" type="text" class="mt-1 block w-full"
                                  value="{{ old('telefon', $dienstleister->telefon ?? '') }}" />
                </div>

                <div>
                    <x-input-label for="email" value="E-Mail" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                  value="{{ old('email', $dienstleister->email ?? '') }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="website" value="Website" />
                    <x-text-input id="website" name="website" type="text" class="mt-1 block w-full"
                                  placeholder="https://..." value="{{ old('website', $dienstleister->website ?? '') }}" />
                </div>
            </div>
        </div>

        {{-- Dienstleistung & Typ --}}
        <div class="bg-gray-50 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Dienstleistung & Typ</h3>
            <div class="space-y-4">

                <div>
                    <x-input-label for="dienstleister_typ" value="Typ / Kategorie" />
                    <select id="dienstleister_typ" name="dienstleister_typ"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">– Bitte wählen –</option>
                        @foreach ($typen as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('dienstleister_typ', $dienstleister->dienstleister_typ ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="fachgebiet" value="Fachgebiet" />
                    <x-text-input id="fachgebiet" name="fachgebiet" type="text" class="mt-1 block w-full"
                                  placeholder="z.B. Netzwerktechnik, Brandschutz..."
                                  value="{{ old('fachgebiet', $dienstleister->fachgebiet ?? '') }}" />
                </div>

                <div>
                    <x-input-label for="leistungsbeschreibung" value="Leistungsbeschreibung" />
                    <textarea id="leistungsbeschreibung" name="leistungsbeschreibung" rows="3"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('leistungsbeschreibung', $dienstleister->leistungsbeschreibung ?? '') }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="kritischer_dienstleister" value="0">
                    <input type="checkbox" id="kritischer_dienstleister" name="kritischer_dienstleister" value="1"
                           class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                           {{ old('kritischer_dienstleister', $dienstleister->kritischer_dienstleister ?? false) ? 'checked' : '' }}>
                    <label for="kritischer_dienstleister" class="text-sm font-medium text-red-600">
                        Kritischer Dienstleister (Ausfallrisiko hoch)
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- RECHTE SPALTE --}}
    <div class="space-y-6">

        {{-- DSGVO --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Datenschutz (DSGVO)</h3>
            <div class="space-y-3">

                <div class="flex items-center gap-2">
                    <input type="hidden" name="verarbeitet_personenbezogene_daten" value="0">
                    <input type="checkbox" id="verarbeitet_personenbezogene_daten" name="verarbeitet_personenbezogene_daten" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           {{ old('verarbeitet_personenbezogene_daten', $dienstleister->verarbeitet_personenbezogene_daten ?? false) ? 'checked' : '' }}>
                    <label for="verarbeitet_personenbezogene_daten" class="text-sm text-gray-700">
                        Verarbeitet personenbezogene Daten
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="av_vertrag_vorhanden" value="0">
                    <input type="checkbox" id="av_vertrag_vorhanden" name="av_vertrag_vorhanden" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           {{ old('av_vertrag_vorhanden', $dienstleister->av_vertrag_vorhanden ?? false) ? 'checked' : '' }}>
                    <label for="av_vertrag_vorhanden" class="text-sm font-medium text-gray-700">
                        AV-Vertrag liegt vor
                    </label>
                </div>

                <div>
                    <x-input-label for="av_vertrag_datum" value="Datum Vertrag" />
                    <x-text-input id="av_vertrag_datum" name="av_vertrag_datum" type="date" class="mt-1 block w-full"
                                  value="{{ old('av_vertrag_datum', isset($dienstleister->av_vertrag_datum) ? $dienstleister->av_vertrag_datum->format('Y-m-d') : '') }}" />
                </div>

                <div>
                    <x-input-label for="av_bemerkungen" value="Bemerkung (Ablageort etc.)" />
                    <x-text-input id="av_bemerkungen" name="av_bemerkungen" type="text" class="mt-1 block w-full"
                                  value="{{ old('av_bemerkungen', $dienstleister->av_bemerkungen ?? '') }}" />
                </div>
            </div>
        </div>

        {{-- Status & Bewertung --}}
        <div class="bg-gray-50 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Status & Bewertung</h3>
            <div class="space-y-4">

                <div>
                    <x-input-label for="status" value="Status *" />
                    <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        @foreach ($status as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('status', $dienstleister->status ?? 'aktiv') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="bewertung_gesamt" value="Gesamtbewertung (1–5 Sterne)" />
                    <select id="bewertung_gesamt" name="bewertung_gesamt"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">– Keine Bewertung –</option>
                        @foreach ([1 => '1 Stern (Mangelhaft)', 2 => '2 Sterne', 3 => '3 Sterne (Durchschnitt)', 4 => '4 Sterne', 5 => '5 Sterne (Exzellent)'] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old('bewertung_gesamt', $dienstleister->bewertung_gesamt ?? '') == $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="empfehlung" value="0">
                    <input type="checkbox" id="empfehlung" name="empfehlung" value="1"
                           class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
                           {{ old('empfehlung', $dienstleister->empfehlung ?? false) ? 'checked' : '' }}>
                    <label for="empfehlung" class="text-sm text-gray-700">
                        👍 Würde ich weiterempfehlen
                    </label>
                </div>

                <div>
                    <x-input-label for="bewertungsnotiz" value="Bewertungsnotiz" />
                    <textarea id="bewertungsnotiz" name="bewertungsnotiz" rows="2"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              placeholder="Kurznotiz zur Bewertung...">{{ old('bewertungsnotiz', $dienstleister->bewertungsnotiz ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Allgemeine Bemerkungen --}}
        <div class="bg-gray-50 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Allgemeine Bemerkungen</h3>
            <textarea name="bemerkungen" rows="4"
                      class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('bemerkungen', $dienstleister->bemerkungen ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- Ansprechpartner --}}
<div class="mt-8" id="ansprechpartner">
    <div class="bg-gray-50 rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Ansprechpartner</h3>
            <div class="flex items-center gap-2">
                {{-- Neue Funktion hinzufügen --}}
                <div x-show="showNeuerFunktion" class="flex items-center gap-1" x-cloak>
                    <input type="text" x-model="neuerFunktionName"
                           @keydown.enter.prevent="saveFunktion()"
                           placeholder="Funktionsbezeichnung..."
                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs px-2 py-1 w-44">
                    <button type="button" @click="saveFunktion()"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-50 text-green-700 hover:bg-green-100 border border-green-200">
                        Speichern
                    </button>
                    <button type="button" @click="showNeuerFunktion = false; neuerFunktionName = ''"
                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-500 hover:bg-gray-200 border border-gray-200">
                        ✕
                    </button>
                </div>
                <button type="button" x-show="!showNeuerFunktion" @click="showNeuerFunktion = true"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-500 hover:bg-gray-200 border border-gray-200">
                    + Funktion anlegen
                </button>
                <button type="button" @click="addAP()"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200">
                    + Ansprechpartner
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <template x-for="(ap, i) in ansprechpartner" :key="i">
                <div class="bg-white border border-gray-200 rounded-lg p-4 relative">
                    <button type="button" @click="removeAP(i)"
                            class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center text-gray-300 hover:text-red-500 text-lg leading-none font-bold">×</button>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Anrede</label>
                            <select :name="'ansprechpartner[' + i + '][anrede]'" x-model="ap.anrede"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">–</option>
                                <option value="Herr">Herr</option>
                                <option value="Frau">Frau</option>
                                <option value="Divers">Divers</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Vorname</label>
                            <input type="text" :name="'ansprechpartner[' + i + '][vorname]'" x-model="ap.vorname"
                                   placeholder="Vorname"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Nachname *</label>
                            <input type="text" :name="'ansprechpartner[' + i + '][nachname]'" x-model="ap.nachname"
                                   placeholder="Nachname"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Funktion</label>
                            <select :name="'ansprechpartner[' + i + '][funktion]'" x-model="ap.funktion"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">– Funktion –</option>
                                <template x-for="f in funktionen" :key="f">
                                    <option :value="f" x-text="f"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Telefon</label>
                            <input type="text" :name="'ansprechpartner[' + i + '][telefon]'" x-model="ap.telefon"
                                   placeholder="Telefon"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Handy</label>
                            <input type="text" :name="'ansprechpartner[' + i + '][handy]'" x-model="ap.handy"
                                   placeholder="Handy"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">E-Mail</label>
                            <input type="email" :name="'ansprechpartner[' + i + '][email]'" x-model="ap.email"
                                   placeholder="E-Mail"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Notiz</label>
                        <textarea :name="'ansprechpartner[' + i + '][notiz]'" x-model="ap.notiz"
                                  rows="2" placeholder="Notiz..."
                                  class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"></textarea>
                    </div>
                </div>
            </template>

            <div x-show="ansprechpartner.length === 0" class="text-center py-6 text-sm text-gray-400 border border-dashed border-gray-200 rounded-lg">
                Noch keine Ansprechpartner. Klicke auf „+ Ansprechpartner" um einen hinzuzufügen.
            </div>
        </div>
    </div>
</div>

</div>{{-- close x-data wrapper --}}
