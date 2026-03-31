@php
    // Gerätetyp: war beim letzten Submit ein freier Wert gewählt?
    $initCustomTyp = old('typ_select') === '__other__' ? 'true' : 'false';

    // Hersteller: war es ein freier Wert?
    $initCustomHersteller = old('hersteller_select') === '__other__' ? 'true' : 'false';

    // Entsorgungsgrund: war es ein freier Wert?
    $initCustomGrund = old('entsorgungsgrund_select') === '__other__' ? 'true' : 'false';
@endphp

<div x-data="{
    grundschutz:      '{{ old('grundschutz', '1') }}',
    customTyp:         {{ $initCustomTyp }},
    customHersteller:  {{ $initCustomHersteller }},
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
                          value="{{ old('name') }}" required placeholder="z. B. Laptop Mustermann" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="modell" value="Modellbezeichnung *" />
            <x-text-input id="modell" name="modell" type="text" class="mt-1 block w-full"
                          value="{{ old('modell') }}" required placeholder="z. B. ThinkPad T14" />
            <x-input-error :messages="$errors->get('modell')" class="mt-1" />
        </div>
    </div>

    {{-- Hersteller (aus Dienstleister-Liste) + Gerätetyp --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Hersteller --}}
        <div>
            <x-input-label for="hersteller_select" value="Hersteller *" />
            <select id="hersteller_select" name="hersteller_select"
                    @change="customHersteller = ($event.target.value === '__other__')"
                    required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— bitte wählen —</option>
                @foreach($dienstleister as $dl)
                    <option value="{{ $dl->id }}"
                        {{ old('hersteller_select') == $dl->id ? 'selected' : '' }}>
                        {{ $dl->firmenname }}
                    </option>
                @endforeach
                <option value="__other__"
                    {{ old('hersteller_select') === '__other__' ? 'selected' : '' }}>
                    Anderer (manuell eingeben)
                </option>
            </select>
            <x-input-error :messages="$errors->get('hersteller_select')" class="mt-1" />
            <div x-show="customHersteller" x-cloak class="mt-2">
                <x-text-input name="hersteller_custom" type="text" class="block w-full"
                              value="{{ old('hersteller_custom') }}"
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
                    <option value="{{ $t }}"
                        {{ old('typ_select') === $t ? 'selected' : '' }}>
                        {{ $t }}
                    </option>
                @endforeach
                <option value="__other__"
                    {{ old('typ_select') === '__other__' ? 'selected' : '' }}>
                    Anderer (manuell eingeben)
                </option>
            </select>
            <x-input-error :messages="$errors->get('typ_select')" class="mt-1" />
            <div x-show="customTyp" x-cloak class="mt-2">
                <x-text-input name="typ_custom" type="text" class="block w-full"
                              value="{{ old('typ_custom') }}"
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
                          value="{{ old('inventar') }}" required
                          placeholder="z. B. 12345 → wird zu 0000012345" />
            <x-input-error :messages="$errors->get('inventar')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="user_id" value="Bisheriger Nutzer des Geräts" />
            <select id="user_id" name="user_id"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— kein Nutzer —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}"
                        {{ old('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
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
                <option value="{{ $g }}"
                    {{ old('entsorgungsgrund_select') === $g ? 'selected' : '' }}>
                    {{ $g }}
                </option>
            @endforeach
            <option value="__other__"
                {{ old('entsorgungsgrund_select') === '__other__' ? 'selected' : '' }}>
                Anderer Grund (manuell eingeben)
            </option>
        </select>
        <x-input-error :messages="$errors->get('entsorgungsgrund_select')" class="mt-1" />
        <div x-show="customGrund" x-cloak class="mt-2">
            <x-text-input name="entsorgungsgrund_custom" type="text" class="block w-full"
                          value="{{ old('entsorgungsgrund_custom') }}"
                          placeholder="Entsorgungsgrund eingeben" />
            <x-input-error :messages="$errors->get('entsorgungsgrund_custom')" class="mt-1" />
        </div>
    </div>

    {{-- BSI Grundschutz --}}
    <div>
        <x-input-label value="BSI-Grundschutz eingehalten? *" />
        <div class="mt-2 flex items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="grundschutz" value="1"
                       x-model="grundschutz"
                       class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Ja</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="grundschutz" value="0"
                       x-model="grundschutz"
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
                  placeholder="Bitte Begründung angeben…">{{ old('grundschutzgrund') }}</textarea>
        <x-input-error :messages="$errors->get('grundschutzgrund')" class="mt-1" />
    </div>

</div>

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush
