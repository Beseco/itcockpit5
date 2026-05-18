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
</div>
