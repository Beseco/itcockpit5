<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800">Neuer Mitarbeiter</h2>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    @php
        $vorlagenData = $vorlagen->mapWithKeys(fn($v) => [
            $v->id => [
                'abteilungId'      => $v->abteilung_id,
                'name'             => $v->name,
                'gruppen'          => $v->gruppen->map(fn($g) => ['dn' => $g->ad_group_dn, 'name' => $g->ad_group_name])->values(),
                'vorgesetzterDn'   => $v->vorgesetzter?->distinguished_name ?? '',
                'vorgesetzterName' => $v->vorgesetzter?->anzeigename_or_name ?? '',
            ],
        ]);
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8"
             x-data="onboardingWizard()">

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Stepper --}}
            <div class="mb-6 flex items-center justify-between">
                <template x-for="(label, i) in stepLabels" :key="i">
                    <div class="flex items-center flex-1 last:flex-none">
                        <button type="button" @click="goTo(i + 1)" :disabled="(i + 1) > maxReached"
                                class="flex items-center gap-2 disabled:cursor-not-allowed">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                  :class="step === (i + 1) ? 'bg-indigo-600 text-white'
                                          : (step > (i + 1) ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400')"
                                  x-text="step > (i + 1) ? '✓' : (i + 1)"></span>
                            <span class="text-sm hidden sm:inline"
                                  :class="step === (i + 1) ? 'font-semibold text-gray-800' : 'text-gray-400'"
                                  x-text="label"></span>
                        </button>
                        <div x-show="i < stepLabels.length - 1" class="flex-1 h-px mx-3 bg-gray-200"></div>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-6" @submit="submitting = true">
                @csrf

                {{-- Hidden Felder, die in allen Steps gültig bleiben --}}
                <input type="hidden" name="vorlage_id" :value="vorlageId">

                {{-- ─── Schritt 1: Vorlage ─────────────────────────────────────── --}}
                <div x-show="step === 1" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Vorlage / Organisationseinheit wählen</h3>
                    <select x-model="vorlageId" @change="selectVorlage()"
                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">– Vorlage auswählen –</option>
                        @foreach($vorlagen as $v)
                            <option value="{{ $v->id }}">
                                {{ $v->name }}{{ $v->abteilung ? ' – ' . $v->abteilung->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('vorlage_id')" class="mt-1" />
                    <p class="mt-2 text-xs text-gray-400">Die Vorlage bestimmt OU, Muster, Standard-Gruppen und Adressdaten.</p>
                </div>

                {{-- ─── Schritt 2: Person ──────────────────────────────────────── --}}
                <div x-show="step === 2" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Vorname &amp; Nachname</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="vorname" value="Vorname *" />
                            <x-text-input id="vorname" name="vorname" type="text" class="mt-1 block w-full"
                                          x-model="vorname" />
                            <x-input-error :messages="$errors->get('vorname')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="nachname" value="Nachname *" />
                            <x-text-input id="nachname" name="nachname" type="text" class="mt-1 block w-full"
                                          x-model="nachname" />
                            <x-input-error :messages="$errors->get('nachname')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- ─── Schritt 3: Kontaktdaten ────────────────────────────────── --}}
                <div x-show="step === 3" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Konto- &amp; Kontaktdaten</h3>
                        <button type="button" @click="loadPreview()" :disabled="previewing"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            <span x-text="previewing ? 'Lade …' : '⟳ Neu ermitteln'"></span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="samaccountname" value="Benutzername (sAMAccountName) *" />
                                <x-text-input id="samaccountname" name="samaccountname" type="text" class="mt-1 block w-full font-mono"
                                              x-model="samaccountname" />
                                <div x-show="alternatives.length > 0" x-cloak class="mt-1 text-xs text-amber-600">
                                    ⚠ Name vergeben. Alternativen:
                                    <template x-for="alt in alternatives" :key="alt">
                                        <button type="button" @click="samaccountname = alt"
                                                class="ml-1 underline hover:text-amber-800" x-text="alt"></button>
                                    </template>
                                </div>
                                <x-input-error :messages="$errors->get('samaccountname')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="upn" value="UPN / E-Mail *" />
                                <x-text-input id="upn" name="upn" type="email" class="mt-1 block w-full font-mono"
                                              x-model="upn" />
                                <x-input-error :messages="$errors->get('upn')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="rufnummer" value="Rufnummer" />
                                <x-text-input id="rufnummer" name="rufnummer" type="text" class="mt-1 block w-full"
                                              x-model="rufnummer" placeholder="Wird automatisch ermittelt" />
                                <x-input-error :messages="$errors->get('rufnummer')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="mobile" value="Mobilnummer" />
                                <x-text-input id="mobile" name="mobile" type="text" class="mt-1 block w-full"
                                              x-model="mobile" placeholder="+49 171 ..." />
                                <x-input-error :messages="$errors->get('mobile')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="buero" value="Büro / Zimmer" />
                                <x-text-input id="buero" name="buero" type="text" class="mt-1 block w-full"
                                              x-model="buero" placeholder="z.B. Zimmer 103" />
                                <x-input-error :messages="$errors->get('buero')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="fax" value="Fax" />
                                <x-text-input id="fax" name="fax" type="text" class="mt-1 block w-full"
                                              x-model="fax" placeholder="Wird automatisch ermittelt" />
                                <x-input-error :messages="$errors->get('fax')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Vorgesetzter" />
                            <p class="mt-1 text-sm" :class="vorgesetzterName ? 'text-gray-800' : 'text-gray-400'">
                                <span x-show="vorgesetzterName" x-text="vorgesetzterName"></span>
                                <span x-show="vorgesetzterName" class="text-xs text-gray-400">(aus Vorlage)</span>
                                <span x-show="!vorgesetzterName">– kein Vorgesetzter hinterlegt –</span>
                            </p>
                            <input type="text" name="vorgesetzter_dn" x-model="vorgesetzterDn"
                                   class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-xs"
                                   placeholder="CN=Max Muster,OU=A1,OU=Benutzer,OU=LRA-FS,DC=lra,DC=lan" />
                            <p class="mt-1 text-xs text-gray-400">AD-DN – aus der Vorlage vorbelegt, kann überschrieben werden (leer = kein Vorgesetzter).</p>
                            <x-input-error :messages="$errors->get('vorgesetzter_dn')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- ─── Schritt 4: Gruppen ─────────────────────────────────────── --}}
                <div x-show="step === 4" class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Gruppen</h3>
                    <p class="text-xs text-gray-400 mb-4">Aus der Vorlage übernommene Gruppen – ergänze bei Bedarf weitere.</p>

                    {{-- Suche --}}
                    <div class="relative mb-4">
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchGroups()"
                               placeholder="Weitere Gruppe suchen (mind. 2 Zeichen) …"
                               class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm pr-8">
                        <span x-show="searching" x-cloak class="absolute right-3 top-2.5 text-xs text-gray-400">…</span>
                        <div x-show="searchResults.length > 0" x-cloak
                             class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                            <template x-for="g in searchResults" :key="g.dn">
                                <button type="button" @click="addGroup(g)"
                                        class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center gap-3">
                                    <span class="flex-1 min-w-0">
                                        <span class="font-medium block" x-text="g.name"></span>
                                        <span class="text-xs text-gray-400 block truncate" x-text="g.dn"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Ausgewählte Gruppen --}}
                    <div class="space-y-2">
                        <template x-for="(g, i) in gruppen" :key="g.dn">
                            <div class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-md">
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-800" x-text="g.name"></span>
                                    <p class="text-xs text-gray-400 truncate mt-0.5" x-text="g.dn"></p>
                                </div>
                                <button type="button" @click="removeGroup(g.dn)"
                                        class="text-xs text-red-500 hover:text-red-700 shrink-0">Entfernen</button>
                                <input type="hidden" :name="'gruppen[' + i + '][dn]'" :value="g.dn">
                                <input type="hidden" :name="'gruppen[' + i + '][name]'" :value="g.name">
                            </div>
                        </template>
                        <p x-show="gruppen.length === 0" class="text-sm text-gray-400">Keine Gruppen ausgewählt.</p>
                    </div>

                    {{-- Vorschläge aus der OU --}}
                    <div class="mt-5 pt-4 border-t border-gray-100" x-show="(ouGroupSuggestions || []).length > 0" x-cloak>
                        <p class="text-xs font-medium text-gray-600 mb-2">
                            Häufige Gruppen der Benutzer in dieser OU (<span x-text="ouCount"></span> Benutzer) – zum Hinzufügen anklicken:
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="g in (ouGroupSuggestions || [])" :key="g.dn">
                                <button type="button"
                                        @click="addGroup({ dn: g.dn, name: g.name })"
                                        :disabled="!!gruppen.find(x => x.dn === g.dn)"
                                        :title="g.dn"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs border border-indigo-200 text-indigo-700 hover:bg-indigo-50 disabled:opacity-40 disabled:cursor-default">
                                    <span x-text="gruppen.find(x => x.dn === g.dn) ? '✓' : '+'"></span>
                                    <span x-text="g.name"></span>
                                    <span class="text-gray-400">(<span x-text="g.count"></span>)</span>
                                </button>
                            </template>
                        </div>
                        <p x-show="ouLoading" x-cloak class="text-xs text-gray-400 mt-2">Lese Gruppen der OU …</p>
                    </div>

                    <div class="mt-5 bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                        Das temporäre Passwort wird nach dem Anlegen <strong>einmalig angezeigt</strong> und nicht gespeichert.
                    </div>
                </div>

                {{-- ─── Navigation ─────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between">
                    <div>
                        <button type="button" x-show="step > 1" @click="prev()"
                                class="text-sm text-gray-600 hover:text-gray-800">← Zurück</button>
                        <a x-show="step === 1" href="{{ route('onboarding.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-800">Abbrechen</a>
                    </div>
                    <div>
                        <button type="button" x-show="step < 4" @click="next()" :disabled="!canAdvance()"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50">
                            Weiter →
                        </button>
                        <x-primary-button x-show="step === 4" type="submit" x-bind:disabled="submitting">
                            <span x-text="submitting ? 'Lege an …' : 'Benutzer jetzt anlegen'"></span>
                        </x-primary-button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function onboardingWizard() {
            return {
                step: 1,
                maxReached: 1,
                stepLabels: ['Vorlage', 'Person', 'Kontaktdaten', 'Gruppen'],
                submitting: false,

                vorlagenData: {{ \Illuminate\Support\Js::from($vorlagenData) }},

                vorlageId: '{{ old("vorlage_id", $vorlage?->id ?? "") }}',
                abteilungId: '',
                vorname: @js(old('vorname', '')),
                nachname: @js(old('nachname', '')),
                samaccountname: @js(old('samaccountname', '')),
                upn: @js(old('upn', '')),
                rufnummer: @js(old('rufnummer', '')),
                mobile: @js(old('mobile', '')),
                fax: @js(old('fax', '')),
                buero: @js(old('buero', '')),
                vorgesetzterDn: @js(old('vorgesetzter_dn', '')),
                vorgesetzterName: '',
                alternatives: [],
                previewing: false,
                previewLoaded: false,

                gruppen: [],
                searchQuery: '',
                searchResults: [],
                searching: false,
                ouGroupSuggestions: [],
                ouCount: 0,
                ouLoading: false,

                init() {
                    if (this.vorlageId) this.selectVorlage(true);
                },

                selectVorlage(keepGruppen = false) {
                    const d = this.vorlagenData[this.vorlageId];
                    this.abteilungId = d ? (d.abteilungId || '') : '';
                    this.vorgesetzterName = d ? (d.vorgesetzterName || '') : '';
                    if (!keepGruppen) {
                        this.gruppen = d ? JSON.parse(JSON.stringify(d.gruppen)) : [];
                        this.vorgesetzterDn = d ? (d.vorgesetzterDn || '') : '';
                    } else if (!this.vorgesetzterDn && d) {
                        // bei erneutem Laden (z.B. Validierungsfehler) Vorlagenwert nur ergänzen
                        this.vorgesetzterDn = d.vorgesetzterDn || '';
                    }
                    this.ouGroupSuggestions = [];
                    this.ouCount = 0;
                    this.previewLoaded = false;
                },

                canAdvance() {
                    if (this.step === 1) return !!this.vorlageId;
                    if (this.step === 2) return this.vorname.trim() !== '' && this.nachname.trim() !== '';
                    if (this.step === 3) return this.samaccountname.trim() !== '' && this.upn.trim() !== '';
                    return true;
                },

                async next() {
                    if (!this.canAdvance()) return;
                    if (this.step === 2 && !this.previewLoaded) await this.loadPreview();
                    if (this.step === 3) await this.loadOuGroups();
                    this.goTo(this.step + 1);
                },

                prev() { this.goTo(this.step - 1); },

                goTo(n) {
                    if (n < 1 || n > 4) return;
                    this.step = n;
                    if (n > this.maxReached) this.maxReached = n;
                },

                async loadPreview() {
                    if (!this.vorlageId || !this.vorname || !this.nachname) return;
                    this.previewing = true;
                    try {
                        const r = await fetch('{{ route("onboarding.preview") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ vorlage_id: this.vorlageId, vorname: this.vorname, nachname: this.nachname }),
                        });
                        const j = await r.json();
                        this.samaccountname = j.samaccountname || '';
                        this.upn = j.upn || '';
                        this.rufnummer = j.rufnummer || '';
                        this.fax = j.fax || '';
                        this.buero = j.buero || '';
                        this.alternatives = j.alternatives || [];
                        this.previewLoaded = true;
                    } catch(e) {}
                    finally { this.previewing = false; }
                },

                async loadOuGroups() {
                    if (!this.abteilungId) { this.ouGroupSuggestions = []; return; }
                    this.ouLoading = true;
                    try {
                        const r = await fetch('{{ route("onboarding.vorlagen.ou-suggestions") }}?abteilung_id=' + encodeURIComponent(this.abteilungId), {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        const j = await r.json();
                        this.ouGroupSuggestions = (j.suggestions && j.suggestions.groups) ? j.suggestions.groups : [];
                        this.ouCount = j.count || 0;
                    } catch(e) { this.ouGroupSuggestions = []; }
                    finally { this.ouLoading = false; }
                },

                async searchGroups() {
                    if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                    this.searching = true;
                    try {
                        const r = await fetch('{{ route("onboarding.vorlagen.search-groups") }}?q=' + encodeURIComponent(this.searchQuery), {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        this.searchResults = await r.json();
                    } catch(e) { this.searchResults = []; }
                    finally { this.searching = false; }
                },

                addGroup(g) {
                    if (!this.gruppen.find(x => x.dn === g.dn)) this.gruppen.push({ dn: g.dn, name: g.name });
                    this.searchQuery = '';
                    this.searchResults = [];
                },

                removeGroup(dn) { this.gruppen = this.gruppen.filter(g => g.dn !== dn); },
            };
        }
    </script>
</x-app-layout>
