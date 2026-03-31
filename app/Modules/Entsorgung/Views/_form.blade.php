<div x-data="{
    grundschutz: '{{ old('grundschutz', '1') }}',
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

    {{-- Hersteller + Typ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="hersteller" value="Hersteller *" />
            <x-text-input id="hersteller" name="hersteller" type="text" class="mt-1 block w-full"
                          value="{{ old('hersteller') }}" required placeholder="z. B. Lenovo" />
            <x-input-error :messages="$errors->get('hersteller')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="typ" value="Gerätetyp" />
            <x-text-input id="typ" name="typ" type="text" class="mt-1 block w-full"
                          value="{{ old('typ') }}" placeholder="z. B. Notebook, Drucker, Monitor" />
            <x-input-error :messages="$errors->get('typ')" class="mt-1" />
        </div>
    </div>

    {{-- Inventarnummer + Bisheriger Nutzer --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="inventar" value="Inventarnummer *" />
            <x-text-input id="inventar" name="inventar" type="text" class="mt-1 block w-full"
                          value="{{ old('inventar') }}" required />
            <x-input-error :messages="$errors->get('inventar')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="user" value="Bisheriger Nutzer des Geräts" />
            <x-text-input id="user" name="user" type="text" class="mt-1 block w-full"
                          value="{{ old('user') }}" placeholder="Vorname Nachname" />
            <x-input-error :messages="$errors->get('user')" class="mt-1" />
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
