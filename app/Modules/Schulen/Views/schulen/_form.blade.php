<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="md:col-span-2">
            <x-input-label for="name" value="Schulname *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          value="{{ old('name', $schule?->name) }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="schultyp" value="Schultyp *" />
            <select id="schultyp" name="schultyp" required
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— Bitte wählen —</option>
                @foreach (\App\Modules\Schulen\Models\Schule::SCHULTYP_LABELS as $val => $label)
                    <option value="{{ $val }}" @selected(old('schultyp', $schule?->schultyp) === $val)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('schultyp')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="sort_order" value="Sortierung" />
            <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full"
                          value="{{ old('sort_order', $schule?->sort_order ?? 0) }}" />
            <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
        </div>
    </div>

    {{-- Adresse --}}
    <div class="border-t border-gray-100 pt-4">
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Adresse</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <x-input-label for="strasse" value="Straße" />
                <x-text-input id="strasse" name="strasse" type="text" class="mt-1 block w-full"
                              value="{{ old('strasse', $schule?->strasse) }}" />
            </div>
            <div>
                <x-input-label for="plz" value="PLZ" />
                <x-text-input id="plz" name="plz" type="text" class="mt-1 block w-full"
                              value="{{ old('plz', $schule?->plz) }}" maxlength="10" />
            </div>
            <div>
                <x-input-label for="ort" value="Ort" />
                <x-text-input id="ort" name="ort" type="text" class="mt-1 block w-full"
                              value="{{ old('ort', $schule?->ort) }}" />
            </div>
        </div>
    </div>

    {{-- Kontaktdaten --}}
    <div class="border-t border-gray-100 pt-4">
        <h4 class="text-sm font-semibold text-gray-600 mb-3">Kontaktdaten</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="telefon" value="Telefon" />
                <x-text-input id="telefon" name="telefon" type="text" class="mt-1 block w-full"
                              value="{{ old('telefon', $schule?->telefon) }}" />
            </div>
            <div>
                <x-input-label for="email" value="E-Mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                              value="{{ old('email', $schule?->email) }}" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="website" value="Website" />
                <x-text-input id="website" name="website" type="url" class="mt-1 block w-full"
                              value="{{ old('website', $schule?->website) }}" placeholder="https://" />
                <x-input-error :messages="$errors->get('website')" class="mt-1" />
            </div>
        </div>
    </div>

    <div>
        <x-input-label for="notizen" value="Notizen" />
        <textarea id="notizen" name="notizen" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('notizen', $schule?->notizen) }}</textarea>
    </div>
</div>
