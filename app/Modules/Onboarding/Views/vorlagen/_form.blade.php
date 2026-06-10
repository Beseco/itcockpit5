{{-- Gemeinsames Formular für Vorlage erstellen/bearbeiten --}}
@php
    $defaults = \App\Modules\Onboarding\Models\OnboardingSettings::getSingleton();
    // Sind in dieser Vorlage individuelle Überschreibungen gesetzt? → Erweitert-Bereich aufklappen
    $hatOverrides = collect([
        $vorlage->samaccountname_pattern ?? null,
        $vorlage->upn_pattern ?? null,
        $vorlage->profilpfad_pattern ?? null,
        $vorlage->heimatverzeichnis_pattern ?? null,
        $vorlage->anmeldeskript ?? null,
    ])->filter(fn($v) => trim((string) $v) !== '')->isNotEmpty();
@endphp
<div class="space-y-6"
     x-data="{
        ouLoading: false,
        ouCount: 0,
        ouError: null,
        sug: {},
        async fetchOu(id) {
            this.sug = {}; this.ouCount = 0; this.ouError = null;
            if (!id) return;
            this.ouLoading = true;
            try {
                const r = await fetch('{{ route('onboarding.vorlagen.ou-suggestions') }}?abteilung_id=' + encodeURIComponent(id), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const j = await r.json();
                this.sug = j.suggestions || {};
                this.ouCount = j.count || 0;
                this.ouError = j.error || null;
            } catch(e) { this.ouError = e.toString(); }
            finally { this.ouLoading = false; }
        },
        apply(field, value) {
            const el = this.$root.querySelector('[name=\'' + field + '\']');
            if (el) { el.value = value; el.dispatchEvent(new Event('input')); }
        }
     }">

    {{-- Basis-Informationen --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-5">Allgemein</h3>
        <div class="space-y-4">

            <div>
                <x-input-label for="name" value="Name der Vorlage *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              value="{{ old('name', $vorlage->name ?? '') }}" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="beschreibung" value="Beschreibung" />
                <textarea id="beschreibung" name="beschreibung" rows="2"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                          >{{ old('beschreibung', $vorlage->beschreibung ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('beschreibung')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="abteilung_id" value="Organisationseinheit (aus Abteilungen)" />
                    <select id="abteilung_id" name="abteilung_id"
                            @change="fetchOu($event.target.value)"
                            x-init="if ($el.value) fetchOu($el.value)"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">– Keine –</option>
                        @foreach($abteilungen as $abt)
                            <option value="{{ $abt->id }}"
                                @selected(old('abteilung_id', $vorlage->abteilung_id ?? '') == $abt->id)>
                                {{ $abt->name }}{{ $abt->ad_path ? ' (' . Str::limit($abt->ad_path, 60) . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Die OU aus der Abteilung wird als Ziel-OU beim Anlegen verwendet.</p>
                    <p x-show="ouLoading" x-cloak class="mt-1 text-xs text-gray-400">Lese Benutzer der OU …</p>
                    <p x-show="!ouLoading && ouCount > 0" x-cloak class="mt-1 text-xs text-indigo-600">
                        <span x-text="ouCount"></span> Benutzer in dieser OU gefunden – Vorschläge werden bei den Adressfeldern angezeigt.
                    </p>
                    <p x-show="ouError" x-cloak class="mt-1 text-xs text-red-600" x-text="ouError"></p>
                    <x-input-error :messages="$errors->get('abteilung_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="vorgesetzter_ad_user_id" value="Standard-Vorgesetzter (AD)" />
                    <select id="vorgesetzter_ad_user_id" name="vorgesetzter_ad_user_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">– Kein –</option>
                        @foreach($adUsers as $adUser)
                            <option value="{{ $adUser->id }}"
                                @selected(old('vorgesetzter_ad_user_id', $vorlage->vorgesetzter_ad_user_id ?? '') == $adUser->id)>
                                {{ $adUser->anzeigename_or_name }} ({{ $adUser->samaccountname }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('vorgesetzter_ad_user_id')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       @checked(old('is_active', $vorlage->is_active ?? true))
                       class="rounded border-gray-300 text-indigo-600">
                <label for="is_active" class="text-sm text-gray-700">Vorlage ist aktiv (erscheint im Onboarding-Formular)</label>
            </div>
        </div>
    </div>

    {{-- Kontaktdaten --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-5">Kontakt & Adresse</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="rufnummer_praefix" value="Rufnummer-Präfix (XX = freie Stelle)" />
                <x-text-input id="rufnummer_praefix" name="rufnummer_praefix" type="text" class="mt-1 block w-full"
                              value="{{ old('rufnummer_praefix', $vorlage->rufnummer_praefix ?? '') }}"
                              placeholder="+498161600314XX" />
                <p class="mt-1 text-xs text-gray-400">XX wird automatisch als nächste freie 2-stellige Nummer aus dem AD ermittelt.</p>
                <template x-if="sug.rufnummer_praefix">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium font-mono" x-text="sug.rufnummer_praefix.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.rufnummer_praefix.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('rufnummer_praefix', sug.rufnummer_praefix.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('rufnummer_praefix')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="fax_praefix" value="Fax-Präfix" />
                <x-text-input id="fax_praefix" name="fax_praefix" type="text" class="mt-1 block w-full"
                              value="{{ old('fax_praefix', $vorlage->fax_praefix ?? '') }}"
                              placeholder="+498161600914XX" />
                <template x-if="sug.fax_praefix">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium font-mono" x-text="sug.fax_praefix.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.fax_praefix.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('fax_praefix', sug.fax_praefix.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('fax_praefix')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="strasse" value="Straße" />
                <x-text-input id="strasse" name="strasse" type="text" class="mt-1 block w-full"
                              value="{{ old('strasse', $vorlage->strasse ?? '') }}" />
                <template x-if="sug.strasse">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium" x-text="sug.strasse.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.strasse.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('strasse', sug.strasse.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('strasse')" class="mt-1" />
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <x-input-label for="plz" value="PLZ" />
                    <x-text-input id="plz" name="plz" type="text" class="mt-1 block w-full"
                                  value="{{ old('plz', $vorlage->plz ?? '') }}" />
                    <template x-if="sug.plz">
                        <p class="mt-1 text-xs text-indigo-600">
                            <span class="font-medium" x-text="sug.plz.value"></span>
                            <button type="button" class="underline ml-1" @click="apply('plz', sug.plz.value)">übern.</button>
                        </p>
                    </template>
                    <x-input-error :messages="$errors->get('plz')" class="mt-1" />
                </div>
                <div class="col-span-2">
                    <x-input-label for="ort" value="Ort" />
                    <x-text-input id="ort" name="ort" type="text" class="mt-1 block w-full"
                                  value="{{ old('ort', $vorlage->ort ?? '') }}" />
                    <template x-if="sug.ort">
                        <p class="mt-1 text-xs text-indigo-600">
                            Vorschlag: <span class="font-medium" x-text="sug.ort.value"></span>
                            <span class="text-gray-400">(<span x-text="sug.ort.count"></span>×)</span>
                            <button type="button" class="underline ml-1" @click="apply('ort', sug.ort.value)">übernehmen</button>
                        </p>
                    </template>
                    <x-input-error :messages="$errors->get('ort')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label for="firma" value="Firma (AD-Attribut company)" />
                <x-text-input id="firma" name="firma" type="text" class="mt-1 block w-full"
                              value="{{ old('firma', $vorlage->firma ?? '') }}" />
                <template x-if="sug.firma">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium" x-text="sug.firma.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.firma.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('firma', sug.firma.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('firma')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="abteilung_ad" value="Abteilung (AD-Attribut department)" />
                <x-text-input id="abteilung_ad" name="abteilung_ad" type="text" class="mt-1 block w-full"
                              value="{{ old('abteilung_ad', $vorlage->abteilung_ad ?? '') }}" />
                <template x-if="sug.abteilung_ad">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium" x-text="sug.abteilung_ad.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.abteilung_ad.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('abteilung_ad', sug.abteilung_ad.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('abteilung_ad')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="buero" value="Büro / Zimmer (AD-Attribut physicalDeliveryOfficeName)" />
                <x-text-input id="buero" name="buero" type="text" class="mt-1 block w-full"
                              value="{{ old('buero', $vorlage->buero ?? '') }}"
                              placeholder="z.B. Zimmer 103" />
                <p class="mt-1 text-xs text-gray-400">Standard für diese Vorlage – kann beim Anlegen überschrieben werden.</p>
                <template x-if="sug.buero">
                    <p class="mt-1 text-xs text-indigo-600">
                        Vorschlag: <span class="font-medium" x-text="sug.buero.value"></span>
                        <span class="text-gray-400">(<span x-text="sug.buero.count"></span>×)</span>
                        <button type="button" class="underline ml-1" @click="apply('buero', sug.buero.value)">übernehmen</button>
                    </p>
                </template>
                <x-input-error :messages="$errors->get('buero')" class="mt-1" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="ad_beschreibung" value="Beschreibung (AD-Attribut description)" />
                <x-text-input id="ad_beschreibung" name="ad_beschreibung" type="text" class="mt-1 block w-full"
                              value="{{ old('ad_beschreibung', $vorlage->ad_beschreibung ?? '') }}"
                              placeholder="z.B. Mitarbeiter Führerscheinstelle" />
                <x-input-error :messages="$errors->get('ad_beschreibung')" class="mt-1" />
            </div>
        </div>
    </div>

    {{-- Erweitert: Muster, Profil & Laufwerke (überschreibt globale Vorgaben) --}}
    <div class="bg-white shadow-sm sm:rounded-lg" x-data="{ open: {{ $hatOverrides ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between p-6 text-left">
            <div>
                <h3 class="text-sm font-semibold text-gray-700">Erweitert – globale Vorgaben überschreiben</h3>
                <p class="text-xs text-gray-400 mt-1">
                    Benutzername-/UPN-Muster, Profil, Heimatverzeichnis und Anmeldeskript.
                    Leer = die globale Vorgabe aus den <a href="{{ route('onboarding.settings') }}" class="underline" @click.stop>Onboarding-Einstellungen</a> wird verwendet.
                </p>
            </div>
            <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak class="px-6 pb-6 space-y-4 border-t border-gray-100 pt-5">
            <p class="text-xs text-gray-400">
                Variablen: <code class="bg-gray-100 px-1 rounded">%vorname%</code>,
                <code class="bg-gray-100 px-1 rounded">%nachname%</code>,
                <code class="bg-gray-100 px-1 rounded">%F%</code>/<code class="bg-gray-100 px-1 rounded">%N%</code> (1. Buchstabe Vor-/Nachname, Groß),
                <code class="bg-gray-100 px-1 rounded">%f%</code>/<code class="bg-gray-100 px-1 rounded">%n%</code> (klein),
                <code class="bg-gray-100 px-1 rounded">%benutzername%</code> (sAMAccountName).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="samaccountname_pattern" value="sAMAccountName-Muster" />
                    <x-text-input id="samaccountname_pattern" name="samaccountname_pattern" type="text" class="mt-1 block w-full font-mono"
                                  value="{{ old('samaccountname_pattern', $vorlage->samaccountname_pattern ?? '') }}"
                                  placeholder="{{ $defaults->default_samaccountname_pattern ?: 'global: –' }}" />
                    <p class="mt-1 text-xs text-gray-400">Leer = global (<code>{{ $defaults->default_samaccountname_pattern ?: '–' }}</code>)</p>
                    <x-input-error :messages="$errors->get('samaccountname_pattern')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="upn_pattern" value="UPN-Muster (E-Mail / Login)" />
                    <x-text-input id="upn_pattern" name="upn_pattern" type="text" class="mt-1 block w-full font-mono"
                                  value="{{ old('upn_pattern', $vorlage->upn_pattern ?? '') }}"
                                  placeholder="{{ $defaults->default_upn_pattern ?: 'global: –' }}" />
                    <p class="mt-1 text-xs text-gray-400">Leer = global (<code>{{ $defaults->default_upn_pattern ?: '–' }}</code>)</p>
                    <x-input-error :messages="$errors->get('upn_pattern')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="profilpfad_pattern" value="Profilpfad-Muster" />
                <x-text-input id="profilpfad_pattern" name="profilpfad_pattern" type="text" class="mt-1 block w-full font-mono text-xs"
                              value="{{ old('profilpfad_pattern', $vorlage->profilpfad_pattern ?? '') }}"
                              placeholder="{{ $defaults->default_profilpfad_pattern ?: '\\\\srv01\\profiles\\%benutzername%' }}" />
                <p class="mt-1 text-xs text-gray-400">Leer = global (<code>{{ $defaults->default_profilpfad_pattern ?: '–' }}</code>)</p>
                <x-input-error :messages="$errors->get('profilpfad_pattern')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="heimatverzeichnis_pattern" value="Heimatverzeichnis-Muster" />
                <div class="mt-1 flex gap-2">
                    <x-text-input id="heimatverzeichnis_pattern" name="heimatverzeichnis_pattern" type="text" class="block flex-1 font-mono text-xs"
                                  value="{{ old('heimatverzeichnis_pattern', $vorlage->heimatverzeichnis_pattern ?? '') }}"
                                  placeholder="{{ $defaults->default_heimatverzeichnis_pattern ?: '\\\\lra.lan\\dfs\\User\\%benutzername%' }}" />
                    <div class="shrink-0">
                        <select name="heimatverzeichnis_laufwerk"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm h-full">
                            <option value="">– global ({{ $defaults->default_heimatverzeichnis_laufwerk ?: '–' }}) –</option>
                            @foreach(range('A', 'Z') as $letter)
                                <option value="{{ $letter }}:"
                                    @selected(old('heimatverzeichnis_laufwerk', $vorlage->heimatverzeichnis_laufwerk ?? '') === $letter . ':')>
                                    {{ $letter }}:
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-400">Der Pfad bestimmt, wo der Heimatordner physisch angelegt wird (inkl. Vollzugriff für den Benutzer). Er wird <strong>nicht</strong> ins AD-Profil geschrieben – die Laufwerkszuweisung übernimmt eine GPO. Leer = global (<code>{{ $defaults->default_heimatverzeichnis_pattern ?: '–' }}</code>).</p>
                <x-input-error :messages="$errors->get('heimatverzeichnis_pattern')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="anmeldeskript" value="Anmeldeskript (scriptPath)" />
                <x-text-input id="anmeldeskript" name="anmeldeskript" type="text" class="mt-1 block w-full font-mono"
                              value="{{ old('anmeldeskript', $vorlage->anmeldeskript ?? '') }}"
                              placeholder="{{ $defaults->default_anmeldeskript ?: 'logon.bat' }}" />
                <p class="mt-1 text-xs text-gray-400">Leer = global (<code>{{ $defaults->default_anmeldeskript ?: '–' }}</code>)</p>
                <x-input-error :messages="$errors->get('anmeldeskript')" class="mt-1" />
            </div>
        </div>
    </div>

    {{-- Gruppen (Sicherheits- & Verteilergruppen) --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6"
         x-data="{
            gruppen: {{ json_encode(old('gruppen', isset($vorlage) ? $vorlage->gruppen->map(fn($g) => ['dn' => $g->ad_group_dn, 'name' => $g->ad_group_name, 'type' => 'security'])->toArray() : [])) }},
            searchQuery: '',
            searchResults: [],
            searching: false,
            async search() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                this.searching = true;
                try {
                    const r = await fetch('{{ route('onboarding.vorlagen.search-groups') }}?q=' + encodeURIComponent(this.searchQuery), {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    this.searchResults = await r.json();
                } catch(e) { this.searchResults = []; }
                finally { this.searching = false; }
            },
            add(g) {
                if (!this.gruppen.find(x => x.dn === g.dn)) this.gruppen.push(g);
                this.searchQuery = ''; this.searchResults = [];
            },
            remove(dn) { this.gruppen = this.gruppen.filter(g => g.dn !== dn); },
            badgeClass(type) {
                return type === 'security'
                    ? 'bg-purple-100 text-purple-700'
                    : 'bg-blue-100 text-blue-700';
            },
            badgeLabel(type) {
                return type === 'security' ? 'Sicherheit' : 'Verteiler';
            }
         }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Gruppen</h3>
            <div class="flex gap-2 text-xs text-gray-400">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full">Sicherheitsgruppe</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">Verteilergruppe</span>
            </div>
        </div>

        {{-- Suche --}}
        <div class="relative mb-4">
            <div class="relative">
                <input type="text" x-model="searchQuery" @input.debounce.300ms="search()"
                       placeholder="Gruppe suchen (mind. 2 Zeichen) …"
                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm pr-8">
                <span x-show="searching" x-cloak class="absolute right-3 top-2 text-xs text-gray-400">…</span>
            </div>
            <div x-show="searchResults.length > 0" x-cloak
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                <template x-for="g in searchResults" :key="g.dn">
                    <button type="button" @click="add(g)"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center gap-3">
                        <span class="flex-1 min-w-0">
                            <span class="font-medium block" x-text="g.name"></span>
                            <span class="text-xs text-gray-400 block truncate" x-text="g.dn"></span>
                        </span>
                        <span :class="badgeClass(g.type)"
                              class="shrink-0 text-xs px-2 py-0.5 rounded-full font-medium"
                              x-text="badgeLabel(g.type)"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Ausgewählte Gruppen --}}
        <div class="space-y-2">
            <template x-for="(g, i) in gruppen" :key="g.dn">
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-md">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-800" x-text="g.name"></span>
                            <span :class="badgeClass(g.type)"
                                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  x-text="badgeLabel(g.type)"></span>
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5" x-text="g.dn"></p>
                    </div>
                    <button type="button" @click="remove(g.dn)"
                            class="text-xs text-red-500 hover:text-red-700 shrink-0">Entfernen</button>
                    <input type="hidden" :name="'gruppen[' + i + '][dn]'" :value="g.dn">
                    <input type="hidden" :name="'gruppen[' + i + '][name]'" :value="g.name">
                    <input type="hidden" :name="'gruppen[' + i + '][type]'" :value="g.type">
                </div>
            </template>
            <p x-show="gruppen.length === 0" class="text-sm text-gray-400">Noch keine Gruppen hinzugefügt.</p>
        </div>
    </div>


</div>
