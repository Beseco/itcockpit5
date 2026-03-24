@php
    $oldMailto     = old('mailto', $reminder->mailto ?? []);
    if (is_string($oldMailto)) $oldMailto = array_filter([$oldMailto]);
    $oldTyp        = old('intervall_typ', $reminder->intervall_typ ?? 'days');
    $cfg           = isset($reminder) ? ($reminder->intervall_config ?? []) : [];
    $oldEvery      = old('config_every',   $cfg['every']   ?? 1);
    $oldDays       = old('config_days',    $cfg['days']    ?? []);
    $oldConfigTime = old('config_time',    $cfg['time']    ?? '08:00');
    $oldNth        = old('config_nth',     $cfg['nth']     ?? 1);
    $oldWeekday    = old('config_weekday', $cfg['weekday'] ?? 'Mo');
    $oldDay        = old('config_day',     $cfg['day']     ?? 1);
    $oldMonth      = old('config_month',   $cfg['month']   ?? 1);
    $oldStartDatum = old('start_datum', isset($reminder) ? $reminder->nextsend->format('d.m.Y') : now()->format('d.m.Y'));
    $oldStartTime  = old('start_time',  isset($reminder) ? $reminder->nextsend->format('H:i') : now()->format('H:i'));
@endphp

<div x-data="{
    typ: '{{ $oldTyp }}',
    days: {{ json_encode($oldDays) }},
    toggleDay(d) {
        if (this.days.includes(d)) {
            this.days = this.days.filter(x => x !== d);
        } else {
            this.days.push(d);
        }
    }
}" class="space-y-6">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Betreff --}}
    <div>
        <x-input-label for="titel" value="Betreff *" />
        <x-text-input id="titel" name="titel" type="text" class="mt-1 block w-full"
                      value="{{ old('titel', $reminder->titel ?? '') }}" required />
        <x-input-error :messages="$errors->get('titel')" class="mt-1" />
    </div>

    {{-- Nachricht --}}
    <div>
        <x-input-label for="nachricht" value="Nachricht (Markdown) *" />
        <textarea id="nachricht" name="nachricht" rows="8"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('nachricht', $reminder->nachricht ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('nachricht')" class="mt-1" />
    </div>

    {{-- Empfänger --}}
    <div x-data="emailTagInput({{ json_encode($oldMailto) }}, {{ json_encode($emailSuggestions ?? []) }})"
         @click.outside="open = false">
        <x-input-label value="Empfänger (E-Mail) *" />

        {{-- Tag-Container --}}
        <div class="mt-1 min-h-[42px] flex flex-wrap gap-1.5 items-center px-3 py-2
                    border border-gray-300 rounded-md bg-white focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 cursor-text"
             @click="$refs.input.focus()">

            <template x-for="(email, i) in tags" :key="i">
                <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 bg-indigo-100 text-indigo-800 text-sm rounded-full">
                    <span x-text="email"></span>
                    <button type="button" @click.stop="removeTag(i)"
                            class="w-4 h-4 rounded-full hover:bg-indigo-200 flex items-center justify-center leading-none text-indigo-600 font-bold">×</button>
                    <input type="hidden" name="mailto[]" :value="email">
                </span>
            </template>

            <input x-ref="input" type="text" x-model="input"
                   @focus="open = true"
                   @input="open = true"
                   @keydown="handleKey($event)"
                   @keydown.escape="open = false"
                   placeholder="E-Mail eingeben …"
                   class="flex-1 min-w-[200px] border-none outline-none p-0 text-sm bg-transparent focus:ring-0">
        </div>

        {{-- Autocomplete-Dropdown --}}
        <div x-show="open && filtered.length > 0" x-cloak
             class="relative">
            <ul class="absolute z-50 w-full bg-white border border-gray-200 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto text-sm">
                <template x-for="s in filtered" :key="s">
                    <li @mousedown.prevent="addTag(s)"
                        class="px-3 py-2 cursor-pointer hover:bg-indigo-50 hover:text-indigo-700"
                        x-text="s"></li>
                </template>
            </ul>
        </div>

        <x-input-error :messages="$errors->get('mailto')" class="mt-1" />
        <x-input-error :messages="$errors->get('mailto.*')" class="mt-1" />
        <p class="mt-1 text-xs text-gray-400">Enter, Tab oder Komma zum Hinzufügen · Backspace zum Entfernen</p>
    </div>

    {{-- Wiederholung: Typ-Auswahl --}}
    <div>
        <x-input-label value="Wiederholung *" />
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach(['minutes'=>'Minuten','hours'=>'Stunden','days'=>'Tage','weekly'=>'Wöchentlich','monthly'=>'Monatlich','yearly'=>'Jährlich'] as $val => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="intervall_typ" value="{{ $val }}" x-model="typ" class="sr-only">
                    <span :class="typ === '{{ $val }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:border-indigo-400'"
                          class="inline-block px-4 py-2 text-sm font-medium border rounded-md transition-colors select-none">
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Konfiguration je Typ --}}
    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4">

        {{-- Minuten --}}
        <div x-show="typ === 'minutes'" x-cloak>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm text-gray-600">Alle</span>
                <select name="config_every" :disabled="typ !== 'minutes'"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach([5 => '5 Minuten', 10 => '10 Minuten', 30 => '30 Minuten'] as $val => $lbl)
                        <option value="{{ $val }}" @selected((int)$oldEvery === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <x-input-error :messages="$errors->get('config_every')" class="mt-1" />
        </div>

        {{-- Stunden / Tage --}}
        <div x-show="typ === 'hours' || typ === 'days'" x-cloak>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm text-gray-600">Alle</span>
                <x-text-input name="config_every" type="number" min="1" class="w-24"
                              value="{{ $oldEvery }}"
                              x-bind:disabled="typ !== 'hours' && typ !== 'days'" />
                <span class="text-sm text-gray-600"
                      x-text="{'hours':'Stunde(n)','days':'Tag(e)'}[typ] ?? ''"></span>
            </div>
            <x-input-error :messages="$errors->get('config_every')" class="mt-1" />
        </div>

        {{-- Wöchentlich: Wochentage --}}
        <div x-show="typ === 'weekly'" x-cloak>
            <p class="text-sm font-medium text-gray-700 mb-2">Wochentage</p>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach(['Mo','Di','Mi','Do','Fr','Sa','So'] as $wt)
                    <button type="button"
                            @click="toggleDay('{{ $wt }}')"
                            :class="days.includes('{{ $wt }}') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300'"
                            class="px-3 py-1.5 text-sm font-medium border rounded-md transition-colors">
                        {{ $wt }}
                    </button>
                    <input type="checkbox" name="config_days[]" value="{{ $wt }}"
                           x-bind:checked="days.includes('{{ $wt }}')"
                           x-bind:disabled="typ !== 'weekly'" class="hidden">
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('config_days')" />
        </div>

        {{-- Monatlich: N-ter Wochentag --}}
        <div x-show="typ === 'monthly'" x-cloak>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm text-gray-600">Am</span>
                <select name="config_nth" :disabled="typ !== 'monthly'"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach([1=>'1.',2=>'2.',3=>'3.',4=>'4.',5=>'5.','last'=>'Letzten'] as $val => $lbl)
                        <option value="{{ $val }}" @selected((string)$oldNth === (string)$val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="config_weekday" :disabled="typ !== 'monthly'"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach(['Mo'=>'Montag','Di'=>'Dienstag','Mi'=>'Mittwoch','Do'=>'Donnerstag','Fr'=>'Freitag','Sa'=>'Samstag','So'=>'Sonntag'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($oldWeekday === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <x-input-error :messages="$errors->get('config_nth')" class="mt-1" />
        </div>

        {{-- Jährlich: Tag.Monat --}}
        <div x-show="typ === 'yearly'" x-cloak>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-600">Am</span>
                <x-text-input name="config_day" type="number" min="1" max="31" class="w-16"
                              value="{{ $oldDay }}" x-bind:disabled="typ !== 'yearly'" />
                <span class="text-sm text-gray-600">.</span>
                <select name="config_month" :disabled="typ !== 'yearly'"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach([1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'] as $val => $lbl)
                        <option value="{{ $val }}" @selected((int)$oldMonth === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <x-input-error :messages="$errors->get('config_day')" class="mt-1" />
            <x-input-error :messages="$errors->get('config_month')" class="mt-1" />
        </div>

        {{-- Uhrzeit (für weekly/monthly/yearly) --}}
        <div x-show="typ === 'weekly' || typ === 'monthly' || typ === 'yearly'" x-cloak>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">Um</span>
                <input type="time" name="config_time" value="{{ $oldConfigTime }}"
                       :disabled="typ !== 'weekly' && typ !== 'monthly' && typ !== 'yearly'"
                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <span class="text-sm text-gray-500">Uhr</span>
            </div>
            <x-input-error :messages="$errors->get('config_time')" class="mt-1" />
        </div>

        {{-- Startdatum (immer) --}}
        <div class="pt-3 border-t border-gray-200">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm text-gray-600 w-32"
                      x-text="(typ==='minutes'||typ==='hours'||typ==='days') ? 'Erster Versand:' : 'Frühestens ab:'">
                </span>
                <x-text-input name="start_datum" type="text" placeholder="TT.MM.JJJJ" class="w-36"
                              value="{{ $oldStartDatum }}" />
                <div x-show="typ === 'minutes' || typ === 'hours' || typ === 'days'" x-cloak
                     class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">um</span>
                    <input type="time" name="start_time" value="{{ $oldStartTime }}"
                           :disabled="typ !== 'minutes' && typ !== 'hours' && typ !== 'days'"
                           class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                </div>
            </div>
            <x-input-error :messages="$errors->get('start_datum')" class="mt-1" />
            <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
        </div>

    </div>

</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>[x-cloak]{display:none!important}</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new EasyMDE({
        element: document.getElementById('nachricht'),
        spellChecker: false,
        autosave: { enabled: false },
        toolbar: ['bold','italic','heading','|','quote','unordered-list','ordered-list','|','link','|','preview','side-by-side','fullscreen'],
        minHeight: '200px',
    });
});

function emailTagInput(initial, suggestions) {
    return {
        tags: Array.isArray(initial) ? [...initial] : [],
        input: '',
        open: false,
        get filtered() {
            if (!this.input) return suggestions.filter(s => !this.tags.includes(s)).slice(0, 8);
            const q = this.input.toLowerCase();
            return suggestions.filter(s => s.toLowerCase().includes(q) && !this.tags.includes(s)).slice(0, 8);
        },
        addTag(email) {
            email = (email || '').trim().toLowerCase();
            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && !this.tags.includes(email)) {
                this.tags.push(email);
            }
            this.input = '';
            this.open = false;
        },
        removeTag(i) {
            this.tags.splice(i, 1);
        },
        handleKey(e) {
            if (['Enter', 'Tab', ',', ';'].includes(e.key)) {
                e.preventDefault();
                if (this.input.trim()) {
                    this.addTag(this.input);
                } else if (e.key === 'Tab') {
                    this.open = false;
                }
            } else if (e.key === 'Backspace' && !this.input && this.tags.length) {
                this.tags.pop();
            }
        }
    };
}
</script>
@endpush
