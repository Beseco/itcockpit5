{{-- Gemeinsames Formular für create und edit --}}

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

    {{-- Betreff --}}
    <div class="sm:col-span-2">
        <x-input-label for="titel" value="Betreff *" />
        <x-text-input id="titel" name="titel" type="text" class="mt-1 block w-full"
                      value="{{ old('titel', $reminder->titel ?? '') }}" required />
        <x-input-error :messages="$errors->get('titel')" class="mt-2" />
    </div>

    {{-- Nachricht --}}
    <div class="sm:col-span-2">
        <x-input-label for="nachricht" value="Nachricht *" />
        <textarea id="nachricht" name="nachricht" rows="6" required
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('nachricht', $reminder->nachricht ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('nachricht')" class="mt-2" />
    </div>

    {{-- Empfänger --}}
    <div class="sm:col-span-2">
        <x-input-label for="mailto" value="Empfänger (E-Mail) *" />
        <x-text-input id="mailto" name="mailto" type="email" class="mt-1 block w-full"
                      value="{{ old('mailto', $reminder->mailto ?? '') }}" required />
        <x-input-error :messages="$errors->get('mailto')" class="mt-2" />
    </div>

    {{-- Datum --}}
    <div>
        <x-input-label for="datum" value="Datum *" />
        <x-text-input id="datum" name="datum" type="text" placeholder="TT.MM.JJJJ"
                      class="mt-1 block w-full"
                      value="{{ old('datum', isset($reminder) ? $reminder->nextsend->format('d.m.Y') : now()->addDay()->format('d.m.Y')) }}"
                      required />
        <x-input-error :messages="$errors->get('datum')" class="mt-2" />
    </div>

    {{-- Uhrzeit --}}
    <div>
        <x-input-label value="Uhrzeit *" />
        <div class="mt-1 flex gap-2">
            <select name="stunde"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @for ($h = 0; $h <= 23; $h++)
                    <option value="{{ $h }}"
                        {{ old('stunde', isset($reminder) ? (int)$reminder->nextsend->format('H') : 8) == $h ? 'selected' : '' }}>
                        {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}
                    </option>
                @endfor
            </select>
            <span class="flex items-center text-gray-500 font-bold">:</span>
            <select name="minute"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach ([0, 15, 30, 45] as $m)
                    <option value="{{ $m }}"
                        {{ old('minute', isset($reminder) ? (int)$reminder->nextsend->format('i') : 0) == $m ? 'selected' : '' }}>
                        {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                    </option>
                @endforeach
            </select>
            <span class="flex items-center text-xs text-gray-400 ml-1">Uhr</span>
        </div>
        <x-input-error :messages="$errors->get('stunde')" class="mt-2" />
        <x-input-error :messages="$errors->get('minute')" class="mt-2" />
    </div>

    {{-- Intervall --}}
    <div class="sm:col-span-2">
        <x-input-label value="Intervall (Wiederholung) *" />
        <div class="mt-1 flex items-center gap-3">
            <span class="text-sm text-gray-500">alle</span>
            <x-text-input name="intervall_nummer" type="number" min="1"
                          class="w-24"
                          value="{{ old('intervall_nummer', $reminder->intervall_nummer ?? 1) }}" required />
            <select name="intervall_faktor"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach ($faktoren as $wert => $label)
                    <option value="{{ $wert }}"
                        {{ old('intervall_faktor', $reminder->intervall_faktor ?? 86400) == $wert ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-input-error :messages="$errors->get('intervall_nummer')" class="mt-2" />
        <x-input-error :messages="$errors->get('intervall_faktor')" class="mt-2" />
    </div>

</div>
