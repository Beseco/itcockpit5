<div class="p-6 space-y-6" x-data="{ open: false, search: '{{ isset($stelle) && $stelle->stelleninhaber ? $stelle->stelleninhaber->name : '' }}', selectedId: '{{ old('user_id', $stelle?->user_id ?? '') }}', users: {{ Js::from($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) }}, get filtered() { if (!this.search) return this.users; const q = this.search.toLowerCase(); return this.users.filter(u => u.name.toLowerCase().includes(q)); }, select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; }, clear() { this.search = ''; this.selectedId = ''; } }">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Stellennummer --}}
        <div>
            <x-input-label for="stellennummer" value="Stellen-Nr. *" />
            <x-text-input id="stellennummer" name="stellennummer" type="text" class="mt-1 block w-full"
                value="{{ old('stellennummer', $stelle?->stellennummer) }}"
                placeholder="z.B. 14.02" required autofocus />
            <x-input-error :messages="$errors->get('stellennummer')" class="mt-1" />
        </div>

        {{-- Stellenbeschreibung --}}
        <div>
            <x-input-label for="stellenbeschreibung_id" value="Stellenbeschreibung *" />
            <select id="stellenbeschreibung_id" name="stellenbeschreibung_id" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">— Bitte wählen —</option>
                @foreach($stellenbeschreibungen as $sb)
                    <option value="{{ $sb->id }}"
                        @selected(old('stellenbeschreibung_id', $stelle?->stellenbeschreibung_id) == $sb->id)>
                        {{ $sb->bezeichnung }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">
                <a href="{{ route('stellenbeschreibungen.index') }}" class="text-indigo-600 hover:underline" target="_blank">Stellenbeschreibungen verwalten ↗</a>
            </p>
            <x-input-error :messages="$errors->get('stellenbeschreibung_id')" class="mt-1" />
        </div>

        {{-- Gruppe --}}
        <div>
            <x-input-label for="gruppe_id" value="Gruppe" />
            <select id="gruppe_id" name="gruppe_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">— Keine Gruppe —</option>
                @foreach($gruppen as $g)
                    <option value="{{ $g->id }}" @selected(old('gruppe_id', $stelle?->gruppe_id) == $g->id)>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Stelleninhaber --}}
        <div>
            <x-input-label value="Stelleninhaber (leer = FREI)" />
            <div class="relative mt-1">
                <input type="hidden" name="user_id" :value="selectedId">
                <input type="text" x-model="search" @focus="open = true" @input="open = true"
                       @keydown.escape="open = false"
                       placeholder="Name suchen oder leer lassen (FREI)..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <button type="button" x-show="selectedId" @click="clear()"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
                <div x-show="open && filtered.length > 0" x-cloak @click.outside="open = false"
                     class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                    <div class="py-1 px-3 text-xs text-gray-500 border-b cursor-pointer hover:bg-gray-50"
                         @click="clear(); open = false">— Stelle ist unbesetzt (FREI) —</div>
                    <template x-for="u in filtered" :key="u.id">
                        <div @click="select(u)" x-text="u.name"
                             class="py-2 px-3 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer"></div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Haushalt-Bewertung --}}
        <div>
            <x-input-label for="haushalt_bewertung" value="HH-Bewertung (Soll)" />
            <x-text-input id="haushalt_bewertung" name="haushalt_bewertung" type="text" class="mt-1 block w-full"
                value="{{ old('haushalt_bewertung', $stelle?->haushalt_bewertung) }}"
                placeholder="z.B. EGr. 11" />
            <x-input-error :messages="$errors->get('haushalt_bewertung')" class="mt-1" />
        </div>

        {{-- Tatsächliche Besoldungsgruppe --}}
        <div>
            <x-input-label for="bes_gruppe" value="Bes.-Gruppe (Ist)" />
            <x-text-input id="bes_gruppe" name="bes_gruppe" type="text" class="mt-1 block w-full"
                value="{{ old('bes_gruppe', $stelle?->bes_gruppe) }}"
                placeholder="z.B. EGr. 10" />
            <x-input-error :messages="$errors->get('bes_gruppe')" class="mt-1" />
        </div>

        {{-- Belegung --}}
        <div>
            <x-input-label for="belegung" value="Belegung der Stelle %" />
            <x-text-input id="belegung" name="belegung" type="number" step="0.01" min="0" max="100"
                class="mt-1 block w-full"
                value="{{ old('belegung', $stelle?->belegung) }}"
                placeholder="z.B. 100" />
            <x-input-error :messages="$errors->get('belegung')" class="mt-1" />
        </div>

        {{-- Gesamtarbeitszeit --}}
        <div>
            <x-input-label for="gesamtarbeitszeit" value="Gesamtarbeitszeit der Dienstkraft %" />
            <x-text-input id="gesamtarbeitszeit" name="gesamtarbeitszeit" type="number" step="0.01" min="0" max="100"
                class="mt-1 block w-full"
                value="{{ old('gesamtarbeitszeit', $stelle?->gesamtarbeitszeit) }}"
                placeholder="z.B. 100" />
            <x-input-error :messages="$errors->get('gesamtarbeitszeit')" class="mt-1" />
        </div>

        {{-- Anteil Stelle --}}
        <div>
            <x-input-label for="anteil_stelle" value="Anteil der Arbeitszeit auf dieser Stelle %" />
            <x-text-input id="anteil_stelle" name="anteil_stelle" type="number" step="0.01" min="0" max="100"
                class="mt-1 block w-full"
                value="{{ old('anteil_stelle', $stelle?->anteil_stelle) }}"
                placeholder="z.B. 100" />
            <x-input-error :messages="$errors->get('anteil_stelle')" class="mt-1" />
        </div>

    </div>

    {{-- Buttons --}}
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <a href="{{ route('stellenplan.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Abbrechen</a>
        <x-primary-button>Speichern</x-primary-button>
    </div>
</div>
