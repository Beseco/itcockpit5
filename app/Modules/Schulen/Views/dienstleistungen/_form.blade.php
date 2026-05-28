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

    {{-- ── Dienstleister-Zuweisung ───────────────────────────────────────── --}}
    @if(isset($alleDienstleister) && $alleDienstleister->isNotEmpty())
    @php
        $selectedIds = collect(old('dienstleister_ids',
            $dienstleistung?->dienstleister?->pluck('id')->toArray() ?? []
        ))->map(fn($v) => (int)$v)->toArray();
    @endphp
    <div x-data="{ search: '' }" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Zugewiesene Dienstleister</h4>
        <input type="text" x-model="search" placeholder="Dienstleister suchen…"
               class="mb-3 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
        <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
            @foreach($alleDienstleister as $dl)
            <label
                x-show="search === '' || '{{ strtolower($dl->firmenname) }}'.includes(search.toLowerCase())"
                class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white cursor-pointer text-sm"
            >
                <input type="checkbox"
                       name="dienstleister_ids[]"
                       value="{{ $dl->id }}"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       @if(in_array($dl->id, $selectedIds)) checked @endif>
                <span class="font-medium text-gray-800">{{ $dl->firmenname }}</span>
                @if($dl->dienstleister_typ)
                    <span class="text-xs text-gray-400">· {{ $dl->dienstleister_typ }}</span>
                @endif
            </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-2">Mehrfachauswahl möglich</p>
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
