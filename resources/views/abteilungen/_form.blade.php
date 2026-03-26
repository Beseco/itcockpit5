{{-- Gemeinsames Formular für create und edit --}}

<div class="space-y-4">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2">
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          value="{{ old('name', $abteilung->name ?? '') }}" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kurzzeichen" value="Kurzzeichen (z.B. SG11)" />
            <x-text-input id="kurzzeichen" name="kurzzeichen" type="text" class="mt-1 block w-full"
                          placeholder="SG11" value="{{ old('kurzzeichen', $abteilung->kurzzeichen ?? '') }}" />
            <x-input-error :messages="$errors->get('kurzzeichen')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="sort_order" value="Reihenfolge" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full"
                          value="{{ old('sort_order', $abteilung->sort_order ?? 0) }}" />
            <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="parent_id" value="Übergeordnete Abteilung" />
            <select id="parent_id" name="parent_id"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">— keine (oberste Ebene) —</option>
                @foreach($allAbteilungen as $a)
                    <option value="{{ $a->id }}"
                        {{ old('parent_id', $abteilung->parent_id ?? '') == $a->id ? 'selected' : '' }}>
                        {{ $a->anzeigename }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
        </div>

    </div>

</div>
