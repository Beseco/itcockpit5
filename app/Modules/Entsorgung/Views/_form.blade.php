@php
    // Im Edit-Modus: gespeicherte Werte als Fallback, sonst old()
    $e   = $eintrag ?? null;
    $val = fn(string $field, $default = '') => old($field, $e?->$field ?? $default);

    // Hersteller
    $currentHersteller    = old('hersteller_select', $e?->hersteller ?? '');
    $herstellerInList     = $hersteller->contains($currentHersteller);
    $initCustomHersteller = (old('hersteller_select') === '__other__' || ($currentHersteller && !$herstellerInList)) ? 'true' : 'false';
    $herstellerSelected   = $herstellerInList ? $currentHersteller : ($currentHersteller ? '__other__' : '');

    // Gerätetyp
    $currentTyp    = old('typ_select', $e?->typ ?? '');
    $typInList     = $typen->contains($currentTyp);
    $initCustomTyp = (old('typ_select') === '__other__' || ($currentTyp && !$typInList)) ? 'true' : 'false';
    $typSelected   = $typInList ? $currentTyp : ($currentTyp ? '__other__' : '');

    // Entsorgungsgrund
    $currentGrund    = old('entsorgungsgrund_select', $e?->entsorgungsgrund ?? '');
    $grundInList     = $gruende->contains($currentGrund);
    $initCustomGrund = (old('entsorgungsgrund_select') === '__other__' || ($currentGrund && !$grundInList)) ? 'true' : 'false';
    $grundSelected   = $grundInList ? $currentGrund : ($currentGrund ? '__other__' : '');

    // AD-Nutzer
    $currentAdUserId   = old('ad_user_id', $e?->ad_user_id ?? '');
    $currentAdUserName = old('ad_user_id')
        ? ($adUsers->firstWhere('id', old('ad_user_id'))?->anzeigenameOrName ?? '')
        : ($e?->nutzer?->anzeigenameOrName ?? '');
@endphp

<div x-data="{
    grundschutz:      '{{ old('grundschutz', $e ? ($e->grundschutz ? '1' : '0') : '1') }}',
    customHersteller:  {{ $initCustomHersteller }},
    customTyp:         {{ $initCustomTyp }},
    customGrund:       {{ $initCustomGrund }},
    get keineEinhaltung() { return this.grundschutz === '0'; }
}" class="space-y-5">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Gerätename + Modell --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="name" value="Gerätename / Bezeichnung *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          value="{{ $val('name') }}" required placeholder="z. B. Laptop Mustermann" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="modell" value="Modellbezeichnung *" />
            <x-text-input id="modell" name="modell" type="text" class="mt-1 block w-full"
                          value="{{ $val('modell') }}" required placeholder="z. B. ThinkPad T14" />
            <x-input-error :messages="$errors->get('modell')" class="mt-1" />
        </div>
    </div>

    {{-- Hersteller + Gerätetyp --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Hersteller --}}
        <div>
            <x-input-label for="hersteller_select" value="Hersteller *" />
            <select id="hersteller_select" name="hersteller_select"
                    @change="customHersteller = ($event.target.value === '__other__')"
                    required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— bitte wählen —</option>
                @foreach($hersteller as $h)
                    <option value="{{ $h }}" {{ $herstellerSelected === $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
                <option value="__other__" {{ $herstellerSelected === '__other__' ? 'selected' : '' }}>
                    Anderer (manuell eingeben)
                </option>
            </select>
            <x-input-error :messages="$errors->get('hersteller_select')" class="mt-1" />
            <div x-show="customHersteller" x-cloak class="mt-2">
                <x-text-input name="hersteller_custom" type="text" class="block w-full"
                              value="{{ $herstellerSelected === '__other__' ? $currentHersteller : old('hersteller_custom', '') }}"
                              placeholder="Herstellername eingeben" />
                <x-input-error :messages="$errors->get('hersteller_custom')" class="mt-1" />
            </div>
        </div>

        {{-- Gerätetyp --}}
        <div>
            <x-input-label for="typ_select" value="Gerätetyp" />
            <select id="typ_select" name="typ_select"
                    @change="customTyp = ($event.target.value === '__other__')"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— kein Typ —</option>
                @foreach($typen as $t)
                    <option value="{{ $t }}" {{ $typSelected === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
                <option value="__other__" {{ $typSelected === '__other__' ? 'selected' : '' }}>
                    Anderer (manuell eingeben)
                </option>
            </select>
            <x-input-error :messages="$errors->get('typ_select')" class="mt-1" />
            <div x-show="customTyp" x-cloak class="mt-2">
                <x-text-input name="typ_custom" type="text" class="block w-full"
                              value="{{ $typSelected === '__other__' ? $currentTyp : old('typ_custom', '') }}"
                              placeholder="Gerätetyp eingeben" />
                <x-input-error :messages="$errors->get('typ_custom')" class="mt-1" />
            </div>
        </div>

    </div>

    {{-- Inventarnummer + Bisheriger Nutzer --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="inventar" value="Inventarnummer * (max. 10 Stellen)" />
            <x-text-input id="inventar" name="inventar" type="text" inputmode="numeric"
                          pattern="\d{1,10}"
                          class="mt-1 block w-full"
                          value="{{ old('inventar', $e ? (ltrim($e->inventar, '0') ?: '0') : '') }}"
                          required placeholder="z. B. 12345 → wird zu 0000012345" />
            <x-input-error :messages="$errors->get('inventar')" class="mt-1" />
        </div>

        {{-- AD-Benutzer Suche --}}
        <div x-data="{
                open: false,
                search: '{{ $currentAdUserName }}',
                selectedId: '{{ $currentAdUserId }}',
                users: {{ Js::from($adUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->anzeigenameOrName])) }},
                get filtered() {
                    if (!this.search) return this.users.slice(0, 10);
                    const q = this.search.toLowerCase();
                    return this.users.filter(u => u.name.toLowerCase().includes(q)).slice(0, 20);
                },
                select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                clear() { this.search = ''; this.selectedId = ''; }
            }" @click.outside="open = false">
            <x-input-label for="ad_user_search" value="Bisheriger Nutzer des Geräts" />
            <input type="hidden" name="ad_user_id" :value="selectedId">
            <div class="relative mt-1">
                <input type="text" id="ad_user_search"
                       x-model="search" @focus="open = true" @input="open = true" @keydown.escape="open = false"
                       placeholder="AD-Benutzer suchen…" autocomplete="off"
                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <button x-show="selectedId" type="button" @click="clear()"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <ul x-show="open && filtered.length > 0" x-cloak
                    class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto">
                    <template x-for="u in filtered" :key="u.id">
                        <li>
                            <button type="button" @click="select(u)"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
                                    x-text="u.name"></button>
                        </li>
                    </template>
                </ul>
            </div>
            <x-input-error :messages="$errors->get('ad_user_id')" class="mt-1" />
        </div>
    </div>

    {{-- Entsorgungsgrund --}}
    <div>
        <x-input-label for="entsorgungsgrund_select" value="Grund der Entsorgung *" />
        <select id="entsorgungsgrund_select" name="entsorgungsgrund_select"
                @change="customGrund = ($event.target.value === '__other__')"
                required
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">— bitte wählen —</option>
            @foreach($gruende as $g)
                <option value="{{ $g }}" {{ $grundSelected === $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
            <option value="__other__" {{ $grundSelected === '__other__' ? 'selected' : '' }}>
                Anderer Grund (manuell eingeben)
            </option>
        </select>
        <x-input-error :messages="$errors->get('entsorgungsgrund_select')" class="mt-1" />
        <div x-show="customGrund" x-cloak class="mt-2">
            <x-text-input name="entsorgungsgrund_custom" type="text" class="block w-full"
                          value="{{ $grundSelected === '__other__' ? $currentGrund : old('entsorgungsgrund_custom', '') }}"
                          placeholder="Entsorgungsgrund eingeben" />
            <x-input-error :messages="$errors->get('entsorgungsgrund_custom')" class="mt-1" />
        </div>
    </div>

    {{-- Grundschutz-Checkliste --}}
    <div x-data="{ open: false }" class="rounded-md border border-blue-200 bg-blue-50">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-blue-800 hover:bg-blue-100 transition rounded-md">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tätigkeiten zum Grundschutz – Checkliste
            </span>
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-cloak class="px-4 pb-4 text-sm text-blue-900 space-y-3">
            <p class="text-xs text-blue-700 border-t border-blue-200 pt-3">
                Diese Checkliste muss bei der Ausmusterung eines IT-Systems komplett bearbeitet werden.
            </p>

            <div>
                <p class="font-semibold mb-1">PCs und Server</p>
                <ul class="list-none space-y-0.5 ml-2">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Festplatten / CDs / SD-Karten / usw. entfernt</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Benötigte lokale Daten archiviert</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. Übergangsphase Alt- auf Neusystem berücksichtigt</li>
                </ul>
                <p class="mt-1 ml-2 text-xs text-blue-600 italic">💡 2 Monate Übergangsphase abwarten, ob Altdaten noch benötigt werden</p>
            </div>

            <div>
                <p class="font-semibold mb-1">Virtuelle Clients und Server</p>
                <p class="ml-2 text-xs mb-1">Das sichere Löschen der Daten von virtuellen Clients und Servern ist nicht notwendig. Es genügt, wenn die jeweiligen Systeme vom Hypervisor gelöscht werden. Wird der komplette Hypervisor ausgemustert, gilt der Punkt „PCs und Server".</p>
                <ul class="list-none space-y-0.5 ml-2">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Benötigte lokale Daten archiviert</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold mb-1">Speichersysteme (SAN / NAS) und sonstige Datenträger</p>
                <ul class="list-none space-y-0.5 ml-2">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Benötigte lokale Daten migriert</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. Übergangsphase Alt- auf Neusystem berücksichtigt</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> System sicher gelöscht</li>
                </ul>
                <p class="mt-1 ml-2 text-xs text-blue-600 italic">💡 2 Monate Übergangsphase abwarten, ob Altdaten noch benötigt werden</p>
                <p class="mt-0.5 ml-2 text-xs text-blue-600 italic">💡 Speichersystem verschlüsseln, komplett zurücksetzen, ggf. erneut verschlüsseln</p>
                <ul class="list-none space-y-0.5 ml-2 mt-1">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> System durch den Hersteller abgeholt und sicher entsorgt</li>
                </ul>
                <p class="mt-0.5 ml-2 text-xs text-blue-600 italic">💡 Datenschutzerklärung / Bestätigung einholen</p>
            </div>

            <div>
                <p class="font-semibold mb-1">Smartphones und Tablets</p>
                <ul class="list-none space-y-0.5 ml-2">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> SIM-Karte entfernt</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. SD-Karte entfernt</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Werks-Reset durchgeführt</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold mb-1">Sonstige IT-Systeme</p>
                <ul class="list-none space-y-0.5 ml-2">
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. benötigte lokale Daten migriert</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. Konfigurationen des Systems gesichert</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> Ggf. Übergangsphase Alt- auf Neusystem berücksichtigt</li>
                    <li class="flex gap-2"><span class="text-blue-500 mt-0.5">•</span> System sicher gelöscht</li>
                </ul>
                <p class="mt-1 ml-2 text-xs text-blue-600 italic">💡 2 Monate Übergangsphase abwarten, ob Altdaten noch benötigt werden</p>
            </div>
        </div>
    </div>

    {{-- BSI Grundschutz --}}
    <div>
        <x-input-label value="BSI-Grundschutz eingehalten? *" />
        <div class="mt-2 flex items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="grundschutz" value="1" x-model="grundschutz"
                       class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Ja</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="grundschutz" value="0" x-model="grundschutz"
                       class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Nein</span>
            </label>
        </div>
        <x-input-error :messages="$errors->get('grundschutz')" class="mt-1" />
    </div>

    {{-- Grundschutz-Begründung (nur wenn Nein) --}}
    <div x-show="keineEinhaltung" x-cloak>
        <x-input-label for="grundschutzgrund" value="Begründung (warum Grundschutz nicht eingehalten) *" />
        <textarea id="grundschutzgrund" name="grundschutzgrund" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                  placeholder="Bitte Begründung angeben…">{{ $val('grundschutzgrund') }}</textarea>
        <x-input-error :messages="$errors->get('grundschutzgrund')" class="mt-1" />
    </div>

</div>

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush
