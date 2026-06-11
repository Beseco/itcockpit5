<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="md:col-span-2">
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          value="{{ old('name', $dienstleistung?->name) }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="dienst_kategorie_id" value="Kategorie" />
            <select id="dienst_kategorie_id" name="dienst_kategorie_id"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— Keine Kategorie —</option>
                @foreach ($kategorien as $kat)
                    <option value="{{ $kat->id }}"
                            @selected(old('dienst_kategorie_id', $dienstleistung?->dienst_kategorie_id) == $kat->id)>
                        {{ $kat->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('dienst_kategorie_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="sort_order" value="Sortierung" />
            <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full"
                          value="{{ old('sort_order', $dienstleistung?->sort_order ?? 0) }}" />
        </div>
    </div>

    <div>
        <x-input-label for="beschreibung" value="Kurzbeschreibung" />
        <textarea id="beschreibung" name="beschreibung" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('beschreibung', $dienstleistung?->beschreibung) }}</textarea>
    </div>

    <div>
        <x-input-label for="dokumentation_url" value="Dokumentations-Link (z.B. Confluence)" />
        <x-text-input id="dokumentation_url" name="dokumentation_url" type="url" class="mt-1 block w-full"
                      value="{{ old('dokumentation_url', $dienstleistung?->dokumentation_url) }}" placeholder="https://" />
        <x-input-error :messages="$errors->get('dokumentation_url')" class="mt-1" />
    </div>

    {{-- Stunden --}}
    <div x-data="{ modus: '{{ old('stunden_modus', $dienstleistung?->stunden_modus ?? 'jahresstunden') }}' }"
         class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Stundenbedarf</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="stunden_modus" value="Eingabe-Modus" />
                <select id="stunden_modus" name="stunden_modus" x-model="modus"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="jahresstunden">Jahresstunden</option>
                    <option value="wochenstunden">Wochenstunden (× 46 = Jahresstunden)</option>
                </select>
            </div>
            <div>
                <x-input-label for="stunden_wert" :value="'Stunden (' . 'wird berechnet' . ')'" />
                <div class="mt-1 relative">
                    <x-text-input id="stunden_wert" name="stunden_wert" type="number" step="0.5" min="0"
                                  class="block w-full pr-24"
                                  value="{{ old('stunden_wert', $dienstleistung?->stunden_wert) }}" />
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gray-400"
                          x-text="modus === 'wochenstunden' ? 'h/Woche' : 'h/Jahr'"></span>
                </div>
                <p class="text-xs text-gray-400 mt-1" x-show="modus === 'wochenstunden'">
                    Wird mit Faktor 46 auf Jahresstunden hochgerechnet.
                </p>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">1 VZE = 1.600 Netto-Jahresstunden (Bayern)</p>
    </div>

    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" id="is_active" name="is_active" value="1"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               @checked(old('is_active', $dienstleistung?->is_active ?? true))>
        <x-input-label for="is_active" value="Dienstleistung ist aktiv (erscheint in Matrix)" />
    </div>

    {{-- Klassifizierung als Betriebsvoraussetzung --}}
    <div class="flex items-start gap-3 border border-gray-200 rounded-lg p-4 bg-gray-50">
        <input type="hidden" name="betriebsvoraussetzung" value="0">
        <input type="checkbox" id="betriebsvoraussetzung" name="betriebsvoraussetzung" value="1"
               class="mt-0.5 rounded border-gray-300 text-gray-600 shadow-sm focus:ring-gray-500"
               @checked(old('betriebsvoraussetzung', $dienstleistung?->betriebsvoraussetzung ?? false))>
        <div>
            <x-input-label for="betriebsvoraussetzung" value="Betriebsvoraussetzung (keine Dienstleistung)" />
            <p class="text-xs text-gray-400 mt-0.5">
                Markiert diesen Eintrag als Voraussetzung, die erfüllt sein muss, damit Dienstleistungen erbracht werden können.
            </p>
        </div>
    </div>

    {{-- ── Dienstleister-Zuweisung ───────────────────────────────────────── --}}
    @if(isset($alleDienstleister) && $alleDienstleister->isNotEmpty())
    @php
        $selectedIds = collect(old('dienstleister_ids',
            $dienstleistung?->dienstleister?->pluck('id')->toArray() ?? []
        ))->map(fn($v) => (int)$v)->toArray();

        $allDlJson = $alleDienstleister->map(fn($dl) => [
            'id'   => $dl->id,
            'name' => $dl->firmenname,
            'typ'  => $dl->dienstleister_typ ?? '',
        ])->values()->toJson();

        $selectedDlJson = $alleDienstleister
            ->whereIn('id', $selectedIds)
            ->map(fn($dl) => [
                'id'   => $dl->id,
                'name' => $dl->firmenname,
                'typ'  => $dl->dienstleister_typ ?? '',
            ])->values()->toJson();
    @endphp

    <div x-data="dienstleisterPicker({{ $allDlJson }}, {{ $selectedDlJson }})"
         class="border border-gray-200 rounded-lg p-4 bg-gray-50">

        <h4 class="text-sm font-semibold text-gray-700 mb-3">Zugewiesene Dienstleister</h4>

        {{-- Ausgewählte Tags --}}
        <div class="flex flex-wrap gap-2 mb-3" x-show="selected.length > 0">
            <template x-for="item in selected" :key="item.id">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full">
                    <span x-text="item.name"></span>
                    <button type="button" @click="remove(item.id)"
                            class="hover:text-indigo-600 text-indigo-400 leading-none">&times;</button>
                    <input type="hidden" name="dienstleister_ids[]" :value="item.id">
                </span>
            </template>
        </div>

        {{-- Suchfeld --}}
        <div class="relative">
            <input type="text"
                   x-model="query"
                   @focus="open = true"
                   @keydown.escape="open = false"
                   @keydown.arrow-down.prevent="focusNext(1)"
                   @keydown.arrow-up.prevent="focusNext(-1)"
                   @keydown.enter.prevent="addFocused()"
                   @click.away="open = false"
                   placeholder="Dienstleister suchen und hinzufügen…"
                   autocomplete="off"
                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">

            {{-- Dropdown --}}
            <div x-show="open && filtered.length > 0"
                 x-cloak
                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto">
                <template x-for="(item, i) in filtered" :key="item.id">
                    <button type="button"
                            @click="add(item); query = ''; open = false"
                            :class="i === focusedIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm border-b border-gray-50 last:border-0 flex items-center justify-between">
                        <span>
                            <span class="font-medium text-gray-800" x-text="item.name"></span>
                            <span x-show="item.typ" class="ml-1.5 text-xs text-gray-400" x-text="'· ' + item.typ"></span>
                        </span>
                        <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </template>
                <div x-show="filtered.length === 0 && query.length > 0"
                     class="px-4 py-3 text-sm text-gray-400">Kein Ergebnis.</div>
            </div>
        </div>

    </div>
    @endif

    {{-- ── Erforderliche Betriebsvoraussetzungen ─────────────────────────── --}}
    @if(isset($alleVoraussetzungen) && $alleVoraussetzungen->isNotEmpty())
    @php
        $selectedVorIds = collect(old('voraussetzung_ids',
            $dienstleistung?->voraussetzungen?->pluck('id')->toArray() ?? []
        ))->map(fn($v) => (int)$v)->toArray();

        $allVorJson = $alleVoraussetzungen->map(fn($v) => [
            'id'   => $v->id,
            'name' => $v->name,
            'typ'  => $v->kategorie?->name ?? '',
        ])->values()->toJson();

        $selectedVorJson = $alleVoraussetzungen
            ->whereIn('id', $selectedVorIds)
            ->map(fn($v) => [
                'id'   => $v->id,
                'name' => $v->name,
                'typ'  => $v->kategorie?->name ?? '',
            ])->values()->toJson();
    @endphp

    <div x-data="dienstleisterPicker({{ $allVorJson }}, {{ $selectedVorJson }})"
         class="border border-gray-200 rounded-lg p-4 bg-gray-50">

        <h4 class="text-sm font-semibold text-gray-700 mb-1">Erforderliche Betriebsvoraussetzungen</h4>
        <p class="text-xs text-gray-400 mb-3">Welche Betriebsvoraussetzungen müssen für diese Dienstleistung erfüllt sein? (Nur zur Dokumentation/Anzeige.)</p>

        <div class="flex flex-wrap gap-2 mb-3" x-show="selected.length > 0">
            <template x-for="item in selected" :key="item.id">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-200 text-gray-800 text-xs font-medium rounded-full">
                    <span x-text="item.name"></span>
                    <button type="button" @click="remove(item.id)"
                            class="hover:text-gray-600 text-gray-400 leading-none">&times;</button>
                    <input type="hidden" name="voraussetzung_ids[]" :value="item.id">
                </span>
            </template>
        </div>

        <div class="relative">
            <input type="text"
                   x-model="query"
                   @focus="open = true"
                   @keydown.escape="open = false"
                   @keydown.arrow-down.prevent="focusNext(1)"
                   @keydown.arrow-up.prevent="focusNext(-1)"
                   @keydown.enter.prevent="addFocused()"
                   @click.away="open = false"
                   placeholder="Betriebsvoraussetzung suchen und hinzufügen…"
                   autocomplete="off"
                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">

            <div x-show="open && filtered.length > 0"
                 x-cloak
                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto">
                <template x-for="(item, i) in filtered" :key="item.id">
                    <button type="button"
                            @click="add(item); query = ''; open = false"
                            :class="i === focusedIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="w-full text-left px-4 py-2.5 text-sm border-b border-gray-50 last:border-0 flex items-center justify-between">
                        <span>
                            <span class="font-medium text-gray-800" x-text="item.name"></span>
                            <span x-show="item.typ" class="ml-1.5 text-xs text-gray-400" x-text="'· ' + item.typ"></span>
                        </span>
                        <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Zuständigkeits-Tabelle ────────────────────────────────────────── --}}
    @php
        $initialRows = old('zustaendigkeiten',
            $dienstleistung?->zustaendigkeiten?->map(fn($z) => [
                'aufgabe'    => $z->aufgabe,
                'lra_it'     => $z->lra_it ?? '',
                'schule_sb'  => $z->schule_sb ?? '',
                'externer_dl'=> $z->externer_dl ?? '',
            ])->toArray() ?? []
        );
    @endphp
    <div x-data="zustaendigkeitenEditor({{ json_encode($initialRows) }})"
         class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-700">Zuständigkeiten</h4>
            <button type="button" @click="addRow()"
                    class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md transition">
                + Zeile hinzufügen
            </button>
        </div>

        {{-- Hinweistext Datalist --}}
        <datalist id="zustaendigkeit-vorschlaege">
            <option value="Vollständig">
            <option value="Koordination">
            <option value="Mitwirkung">
            <option value="Meldung">
            <option value="—">
        </datalist>

        <div x-show="rows.length === 0" class="text-sm text-gray-400 py-2">
            Noch keine Zeilen – „Zeile hinzufügen" klicken.
        </div>

        <div x-show="rows.length > 0" class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="pb-2 text-left text-xs font-medium text-gray-500 pr-2 w-1/3">Aufgabe</th>
                        <th class="pb-2 text-left text-xs font-medium text-gray-500 pr-2">LRA Freising IT</th>
                        <th class="pb-2 text-left text-xs font-medium text-gray-500 pr-2">Schule / SB</th>
                        <th class="pb-2 text-left text-xs font-medium text-gray-500 pr-2">Externer DL</th>
                        <th class="pb-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, i) in rows" :key="i">
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-1.5 pr-2">
                                <input type="text" :name="`zustaendigkeiten[${i}][aufgabe]`"
                                       x-model="row.aufgabe" required
                                       placeholder="Aufgabe…"
                                       class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded text-sm py-1">
                            </td>
                            <td class="py-1.5 pr-2">
                                <input type="text" :name="`zustaendigkeiten[${i}][lra_it]`"
                                       x-model="row.lra_it"
                                       list="zustaendigkeit-vorschlaege"
                                       placeholder="Beteiligung…"
                                       class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded text-sm py-1">
                            </td>
                            <td class="py-1.5 pr-2">
                                <input type="text" :name="`zustaendigkeiten[${i}][schule_sb]`"
                                       x-model="row.schule_sb"
                                       list="zustaendigkeit-vorschlaege"
                                       placeholder="Beteiligung…"
                                       class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded text-sm py-1">
                            </td>
                            <td class="py-1.5 pr-2">
                                <input type="text" :name="`zustaendigkeiten[${i}][externer_dl]`"
                                       x-model="row.externer_dl"
                                       list="zustaendigkeit-vorschlaege"
                                       placeholder="Beteiligung…"
                                       class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded text-sm py-1">
                            </td>
                            <td class="py-1.5 text-center">
                                <button type="button" @click="removeRow(i)"
                                        class="text-gray-300 hover:text-red-500 transition text-lg leading-none"
                                        title="Zeile entfernen">&times;</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dienstleisterPicker(all, preselected) {
    return {
        all,
        selected: preselected,
        query: '',
        open: false,
        focusedIndex: -1,
        get filtered() {
            const q = this.query.toLowerCase();
            const selectedIds = this.selected.map(s => s.id);
            return this.all.filter(item =>
                !selectedIds.includes(item.id) &&
                (q === '' || item.name.toLowerCase().includes(q))
            );
        },
        add(item) {
            if (!this.selected.find(s => s.id === item.id)) {
                this.selected.push(item);
            }
            this.focusedIndex = -1;
        },
        remove(id) {
            this.selected = this.selected.filter(s => s.id !== id);
        },
        focusNext(dir) {
            this.open = true;
            this.focusedIndex = Math.max(-1, Math.min(this.filtered.length - 1, this.focusedIndex + dir));
        },
        addFocused() {
            if (this.focusedIndex >= 0 && this.filtered[this.focusedIndex]) {
                this.add(this.filtered[this.focusedIndex]);
                this.query = '';
                this.open = false;
            }
        }
    }
}

function zustaendigkeitenEditor(initial) {
    return {
        rows: initial.length ? initial : [],
        addRow() {
            this.rows.push({ aufgabe: '', lra_it: '', schule_sb: '', externer_dl: '' });
        },
        removeRow(i) {
            this.rows.splice(i, 1);
        }
    }
}
</script>
@endpush
