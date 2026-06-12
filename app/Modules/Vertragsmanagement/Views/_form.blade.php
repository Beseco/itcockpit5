{{-- Gemeinsames Formular für Vertrag anlegen/bearbeiten. Erwartet $vertrag (oder null) + $dienstleister --}}
<div class="space-y-5">

    <div>
        <x-input-label for="name" value="Vertragsname *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $vertrag?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="dienstleister_id" value="Dienstleister" />
        <select id="dienstleister_id" name="dienstleister_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">— Kein Dienstleister —</option>
            @foreach($dienstleister as $dl)
                <option value="{{ $dl->id }}" @selected(old('dienstleister_id', $vertrag?->dienstleister_id) == $dl->id)>
                    {{ $dl->firmenname }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('dienstleister_id')" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="vertragsbeginn" value="Vertragsbeginn *" />
            <x-text-input id="vertragsbeginn" name="vertragsbeginn" type="date" class="mt-1 block w-full"
                          value="{{ old('vertragsbeginn', $vertrag?->vertragsbeginn?->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('vertragsbeginn')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="vertragsende" value="Vertragsende" />
            <x-text-input id="vertragsende" name="vertragsende" type="date" class="mt-1 block w-full"
                          value="{{ old('vertragsende', $vertrag?->vertragsende?->format('Y-m-d')) }}" />
            <p class="mt-1 text-xs text-gray-400">Leer lassen = unbefristeter Vertrag (keine Erinnerung).</p>
            <x-input-error :messages="$errors->get('vertragsende')" class="mt-1" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="kuendigungsfrist_monate" value="Kündigungsfrist (Monate)" />
            <x-text-input id="kuendigungsfrist_monate" name="kuendigungsfrist_monate" type="number" min="0" max="120"
                          class="mt-1 block w-full"
                          value="{{ old('kuendigungsfrist_monate', $vertrag?->kuendigungsfrist_monate) }}" />
            <x-input-error :messages="$errors->get('kuendigungsfrist_monate')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="erinnerung_vorlauf_wochen" value="Erinnerung Vorlauf (Wochen) *" />
            <x-text-input id="erinnerung_vorlauf_wochen" name="erinnerung_vorlauf_wochen" type="number" min="1" max="52"
                          class="mt-1 block w-full"
                          value="{{ old('erinnerung_vorlauf_wochen', $vertrag?->erinnerung_vorlauf_wochen ?? 4) }}" required />
            <p class="mt-1 text-xs text-gray-400">Ab wie vielen Wochen vor Vertragsende wöchentlich erinnert wird.</p>
            <x-input-error :messages="$errors->get('erinnerung_vorlauf_wochen')" class="mt-1" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="benachrichtigungs_email" value="Benachrichtigungs-E-Mail" />
            <x-text-input id="benachrichtigungs_email" name="benachrichtigungs_email" type="email"
                          class="mt-1 block w-full"
                          value="{{ old('benachrichtigungs_email', $vertrag?->benachrichtigungs_email) }}"
                          placeholder="optional" />
            <p class="mt-1 text-xs text-gray-400">Leer = Fallback-E-Mail aus den <a href="{{ route('vertragsmanagement.settings') }}" class="underline">Einstellungen</a>.</p>
            <x-input-error :messages="$errors->get('benachrichtigungs_email')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="status" value="Status *" />
            <select id="status" name="status"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                @foreach(\App\Modules\Vertragsmanagement\Models\Vertrag::STATUS as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $vertrag?->status ?? 'aktiv') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-1" />
        </div>
    </div>

    <div>
        <x-input-label for="notizen" value="Notizen" />
        <textarea id="notizen" name="notizen" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('notizen', $vertrag?->notizen) }}</textarea>
        <x-input-error :messages="$errors->get('notizen')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="dokumente" value="Vertragsdokumente (PDF)" />
        <input id="dokumente" name="dokumente[]" type="file" accept="application/pdf" multiple
               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
        <p class="mt-1 text-xs text-gray-400">Mehrere PDF-Dateien möglich, max. 20 MB je Datei.</p>
        <x-input-error :messages="$errors->get('dokumente.0')" class="mt-1" />
        <x-input-error :messages="$errors->get('dokumente')" class="mt-1" />
    </div>
</div>
