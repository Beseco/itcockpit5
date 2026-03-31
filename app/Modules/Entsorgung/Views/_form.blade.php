@php
    // Im Edit-Modus: gespeicherte Werte als Fallback, sonst old()
    $val = fn(string $field, $default = '') => old($field, isset($eintrag) ? $eintrag->$field : $default);

    // Hersteller
    $currentHersteller    = old('hersteller_select', $eintrag->hersteller ?? '');
    $herstellerInList     = $hersteller->contains($currentHersteller);
    $initCustomHersteller = (old('hersteller_select') === '__other__' || ($currentHersteller && !$herstellerInList)) ? 'true' : 'false';
    $herstellerSelected   = $herstellerInList ? $currentHersteller : ($currentHersteller ? '__other__' : '');

    // Gerätetyp
    $currentTyp    = old('typ_select', $eintrag->typ ?? '');
    $typInList     = $typen->contains($currentTyp);
    $initCustomTyp = (old('typ_select') === '__other__' || ($currentTyp && !$typInList)) ? 'true' : 'false';
    $typSelected   = $typInList ? $currentTyp : ($currentTyp ? '__other__' : '');

    // Entsorgungsgrund
    $currentGrund    = old('entsorgungsgrund_select', $eintrag->entsorgungsgrund ?? '');
    $grundInList     = $gruende->contains($currentGrund);
    $initCustomGrund = (old('entsorgungsgrund_select') === '__other__' || ($currentGrund && !$grundInList)) ? 'true' : 'false';
    $grundSelected   = $grundInList ? $currentGrund : ($currentGrund ? '__other__' : '');

    // AD-Nutzer
    $currentAdUserId   = old('ad_user_id', $eintrag->ad_user_id ?? '');
    $currentAdUserName = old('ad_user_id')
        ? ($adUsers->firstWhere('id', old('ad_user_id'))?->anzeigenameOrName ?? '')
        : ($eintrag->nutzer?->anzeigenameOrName ?? '');
@endphp

<div x-data="{
    grundschutz:      '{{ old('grundschutz', isset($eintrag) ? ($eintrag->grundschutz ? '1' : '0') : '1') }}',
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
                          value="{{ old('inventar', isset($eintrag) ? ltrim($eintrag->inventar, '0') ?: '0' : '') }}"
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
