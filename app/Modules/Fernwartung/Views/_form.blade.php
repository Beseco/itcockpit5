@php
    $fw ??= null;
    $isEdit = $fw !== null;
    // Tool-Auswahl
    $currentTool  = old('tool_select', $fw?->tool ?? '');
    $toolInList   = $tools->pluck('name')->contains($currentTool);
    $isCustomTool = $isEdit && !$toolInList && $currentTool !== '';
    $initCustom   = $isCustomTool ? 'true' : 'false';
    $initToolSel  = $isCustomTool ? '__other__' : $currentTool;
    // Firma: alter Wert oder Firma des vorhandenen Eintrags
    $currentFirma = old('firma_select', $fw?->firma ?? '');
    $initFirmaSel = $currentFirma;
    // Beobachter-Default: eingeloggter User (nur bei Neuerstellung)
    $defaultBeobachter = old('beobachter_user_id', $fw?->beobachter_user_id ?? auth()->id());
@endphp

<div x-data="{
    customTool: {{ $initCustom }},
    setTool(val) { this.customTool = (val === '__other__'); }
}" class="space-y-5">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Firma (Dienstleister-Auswahl) + Name des Externen --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Firma --}}
        <div>
            <x-input-label for="firma_select" value="Firma *" />
            <select id="firma_select" name="firma_select"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">— bitte wählen —</option>
                @foreach($dienstleister as $dl)
                    <option value="{{ $dl->firmenname }}" @selected($initFirmaSel === $dl->firmenname)>
                        {{ $dl->firmenname }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                Nicht dabei?
                <a href="{{ route('dienstleister.create') }}" target="_blank"
                   class="text-indigo-600 hover:text-indigo-800 underline">
                    Neuen Dienstleister anlegen ↗
                </a>
                – danach diese Seite neu laden.
            </p>
            <x-input-error :messages="$errors->get('firma_select')" class="mt-1" />
        </div>

        {{-- Name des Externen --}}
        <div>
            <x-input-label for="externer_name" value="Name des Externen *" />
            <x-text-input id="externer_name" name="externer_name" type="text" class="mt-1 block w-full"
                          value="{{ old('externer_name', $fw?->externer_name) }}" required placeholder="Vorname Nachname" />
            <x-input-error :messages="$errors->get('externer_name')" class="mt-1" />
        </div>
    </div>

    {{-- Beobachter (User-Auswahl, Default = eingeloggter User) --}}
    <div>
        <x-input-label for="beobachter_user_id" value="Beobachter / Admin *" />
        <select id="beobachter_user_id" name="beobachter_user_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">— bitte wählen —</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($defaultBeobachter == $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('beobachter_user_id')" class="mt-1" />
    </div>

    {{-- Ziel --}}
    <div>
        <x-input-label for="ziel" value="Fernwartungsziel *" />
        <x-text-input id="ziel" name="ziel" type="text" class="mt-1 block w-full"
                      value="{{ old('ziel', $fw?->ziel) }}" required placeholder="Server- oder PC-Name" />
        <x-input-error :messages="$errors->get('ziel')" class="mt-1" />
    </div>

    {{-- Tool --}}
    <div>
        <x-input-label for="tool_select" value="Fernwartungstool *" />
        <select id="tool_select" name="tool_select"
                @change="setTool($event.target.value)"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">— bitte wählen —</option>
            @foreach($tools as $t)
                <option value="{{ $t->name }}" @selected($initToolSel === $t->name)>{{ $t->name }}</option>
            @endforeach
            <option value="__other__" @selected($initToolSel === '__other__')>Anderer (manuell eingeben)</option>
        </select>
        <div x-show="customTool" x-cloak class="mt-2">
            <x-text-input name="tool_custom" type="text" class="block w-full"
                          value="{{ old('tool_custom', $isCustomTool ? $currentTool : '') }}"
                          placeholder="Tool-Name eingeben" />
        </div>
        <x-input-error :messages="$errors->get('tool_select')" class="mt-1" />
        <x-input-error :messages="$errors->get('tool_custom')" class="mt-1" />
    </div>

    {{-- Datum --}}
    <div>
        <x-input-label for="datum" value="Datum *" />
        <x-text-input id="datum" name="datum" type="date" class="mt-1 block w-48"
                      value="{{ old('datum', $fw?->datum?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
        <x-input-error :messages="$errors->get('datum')" class="mt-1" />
    </div>

    {{-- Beginn + Ende --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="beginn" value="Beginn *" />
            <div class="mt-1 flex items-center gap-2">
                <input id="beginn" name="beginn" type="time"
                       value="{{ old('beginn', $fw?->beginn ?? '') }}" required
                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <button type="button"
                        onclick="document.getElementById('beginn').value = new Date().toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit',hour12:false})"
                        class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 transition-colors">
                    Jetzt
                </button>
            </div>
            <x-input-error :messages="$errors->get('beginn')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="ende" value="Ende" />
            <div class="mt-1 flex items-center gap-2">
                <input id="ende" name="ende" type="time"
                       value="{{ old('ende', $fw?->ende ?? '') }}"
                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <button type="button"
                        onclick="document.getElementById('ende').value = new Date().toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit',hour12:false})"
                        class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md hover:bg-indigo-100 transition-colors">
                    Jetzt
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">Kann auch später aus der Liste gesetzt werden.</p>
            <x-input-error :messages="$errors->get('ende')" class="mt-1" />
        </div>
    </div>

    {{-- Grund --}}
    <div>
        <x-input-label for="grund" value="Grund und Initiator *" />
        <textarea id="grund" name="grund" rows="4"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                  required placeholder="Wer hat die Fernwartung initiiert und aus welchem Grund?">{{ old('grund', $fw?->grund) }}</textarea>
        <x-input-error :messages="$errors->get('grund')" class="mt-1" />
    </div>

</div>

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush
