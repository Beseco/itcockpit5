<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision: {{ $app->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen py-10 px-4">
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="bg-indigo-700 rounded-t-xl px-8 py-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-300 mb-0.5">IT Cockpit · Abteilungsrevision</p>
        <h1 class="text-xl font-bold text-white">{{ $abteilung->anzeigename }}</h1>
    </div>

    {{-- Fortschritt --}}
    <div class="bg-indigo-600 px-8 py-2 flex items-center justify-between text-sm text-indigo-200">
        <span>Applikation {{ $current }} von {{ $total }}</span>
        <div class="flex gap-1">
            @for($i = 1; $i <= $total; $i++)
                <span class="w-2.5 h-2.5 rounded-full {{ $i < $current ? 'bg-indigo-300' : ($i === $current ? 'bg-white' : 'bg-indigo-500') }}"></span>
            @endfor
        </div>
    </div>

    @php $startInEdit = $errors->any() || old('_mode') === 'edit'; @endphp

    <div x-data="{ mode: '{{ $startInEdit ? 'edit' : 'view' }}' }"
         class="bg-white rounded-b-xl shadow border border-t-0 border-gray-200">

        {{-- App-Name + Schutzbedarf-Badges (immer sichtbar) --}}
        <div class="px-8 pt-6 pb-4 border-b border-gray-100">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ $app->name }}</h2>
                    @if($app->abteilung)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $app->abteilung->anzeigename }}</p>
                    @endif
                </div>
                <div class="flex gap-1 shrink-0">
                    @php $sbLabels = ['A' => 'Normal', 'B' => 'Hoch', 'C' => 'Sehr hoch']; @endphp
                    @foreach(['C' => $app->confidentiality, 'I' => $app->integrity, 'V' => $app->availability] as $lbl => $val)
                        <span class="text-xs font-bold px-2 py-0.5 rounded
                            {{ $val === 'C' ? 'bg-red-100 text-red-700' : ($val === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}"
                              title="{{ ['C'=>'Vertraulichkeit','I'=>'Integrität','V'=>'Verfügbarkeit'][$lbl] }}: {{ $sbLabels[$val] ?? $val }}">
                            {{ $lbl }}:{{ $val }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- VIEW-MODUS: Alle Felder read-only                         --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div x-show="mode === 'view'" x-cloak>
            <div class="px-8 py-5">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if($app->einsatzzweck)
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Beschreibung</dt>
                        <dd class="text-gray-700">{{ $app->einsatzzweck }}</dd>
                    </div>
                    @endif
                    @if($app->baustein)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Typ / Baustein</dt>
                        <dd class="text-gray-700">{{ $app->baustein }}</dd>
                    </div>
                    @endif
                    @if($app->hersteller)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Hersteller</dt>
                        <dd class="text-gray-700">{{ $app->hersteller }}</dd>
                    </div>
                    @endif
                    @if($app->adminUser)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">IT-Administrator</dt>
                        <dd class="text-gray-700">{{ $app->adminUser->name }}</dd>
                    </div>
                    @endif
                    @if($app->verantwortlichAdUser)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Verfahrensverantwortlicher</dt>
                        <dd class="text-gray-700">{{ $app->verantwortlichAdUser->anzeigename }}</dd>
                    </div>
                    @endif
                    @if($app->ansprechpartner)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Ansprechpartner</dt>
                        <dd class="text-gray-700">{{ $app->ansprechpartner }}</dd>
                    </div>
                    @endif
                    @if($app->sachgebiet)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Sachgebiet</dt>
                        <dd class="text-gray-700">{{ $app->sachgebiet }}</dd>
                    </div>
                    @endif
                    @if($app->revision_date)
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Revisionsdatum</dt>
                        <dd class="text-gray-700">{{ $app->revision_date->format('d.m.Y') }}</dd>
                    </div>
                    @endif
                    @if($app->servers->isNotEmpty())
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Server</dt>
                        <dd class="text-gray-700">{{ $app->servers->pluck('name')->join(', ') }}</dd>
                    </div>
                    @endif

                    {{-- Schutzbedarf ausgeschrieben --}}
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Vertraulichkeit</dt>
                        <dd>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded
                                {{ $app->confidentiality === 'C' ? 'bg-red-100 text-red-700' : ($app->confidentiality === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ $sbLabels[$app->confidentiality] ?? $app->confidentiality }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Integrität</dt>
                        <dd>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded
                                {{ $app->integrity === 'C' ? 'bg-red-100 text-red-700' : ($app->integrity === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ $sbLabels[$app->integrity] ?? $app->integrity }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-400 mb-0.5">Verfügbarkeit</dt>
                        <dd>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded
                                {{ $app->availability === 'C' ? 'bg-red-100 text-red-700' : ($app->availability === 'B' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ $sbLabels[$app->availability] ?? $app->availability }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="px-8 pb-6 pt-1 flex items-center justify-between border-t border-gray-100">
                {{-- Applikation bearbeiten --}}
                <button @click="mode = 'edit'"
                        class="inline-flex items-center px-5 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 text-sm transition">
                    Applikation bearbeiten
                </button>

                {{-- Bestätigen und Weiter --}}
                <form action="{{ route('abteilung-revision.app.submit', [$token, $app->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="skip" value="1">
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 text-sm transition">
                        Bestätigen und Weiter →
                    </button>
                </form>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════ --}}
        {{-- EDIT-MODUS: Nur editierbare Felder                        --}}
        {{-- ══════════════════════════════════════════════════════════ --}}
        <div x-show="mode === 'edit'" x-cloak>
            <div class="px-8 pt-4 pb-1">
                <button @click="mode = 'view'" type="button"
                        class="text-xs text-indigo-500 hover:text-indigo-700">← Zurück zur Übersicht</button>
            </div>

            @if($errors->any())
                <div class="mx-8 mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('abteilung-revision.app.submit', [$token, $app->id]) }}" method="POST"
                  class="px-8 pb-7 pt-2 space-y-5">
                @csrf
                <input type="hidden" name="_mode" value="edit">

                {{-- App nicht mehr vorhanden --}}
                <div x-data="{ checked: {{ old('nicht_vorhanden') ? 'true' : 'false' }} }"
                     class="rounded-lg border p-4"
                     :class="checked ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50'">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="nicht_vorhanden" value="1"
                               x-model="checked"
                               class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-800">App nicht mehr vorhanden / nicht mehr benötigt</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Markieren Sie dies, wenn die Software in Ihrer Abteilung nicht mehr eingesetzt wird. Die IT-Abteilung wird darüber informiert.</span>
                        </span>
                    </label>
                </div>

                {{-- Beschreibung --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Beschreibung / Einsatzzweck
                    </label>
                    <textarea name="einsatzzweck" rows="3"
                              class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('einsatzzweck', $app->einsatzzweck) }}</textarea>
                </div>

                {{-- Ansprechpartner (AD-Suche) --}}
                <div x-data="adSearch('ansprechpartner', {{ json_encode(old('ansprechpartner', $app->ansprechpartner)) }}, adUsers)" class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ansprechpartner
                        <span class="text-xs font-normal text-gray-400 ml-1">(Freitext oder aus AD suchen)</span>
                    </label>
                    <input type="hidden" name="ansprechpartner" :value="value">
                    <input type="text"
                           x-model="query"
                           @input="onInput"
                           @focus="open = query.length > 0"
                           @keydown.escape="open = false"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="selectHighlighted()"
                           @blur="onBlur"
                           placeholder="Name eingeben oder aus AD suchen..."
                           autocomplete="off"
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                    <div x-show="open && results.length > 0"
                         class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-52 overflow-auto text-sm">
                        <template x-for="(item, i) in results" :key="item.id">
                            <div @mousedown.prevent="select(item)"
                                 :class="i === highlighted ? 'bg-indigo-50 text-indigo-800' : 'text-gray-800 hover:bg-gray-50'"
                                 class="px-3 py-2 cursor-pointer">
                                <span x-text="item.anzeigename" class="font-medium"></span>
                                <span x-show="item.email" x-text="' (' + item.email + ')'" class="text-gray-400 text-xs"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Verfahrensverantwortlicher --}}
                <div x-data="adSearch('verantwortlich_ad_user_id', null, adUsers, {{ $app->verantwortlich_ad_user_id ?? 'null' }}, {{ json_encode($app->verantwortlichAdUser?->anzeigename ?? '') }})" class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Verfahrensverantwortlicher</label>
                    <input type="hidden" name="verantwortlich_ad_user_id" :value="selectedId">
                    <input type="text"
                           x-model="query"
                           @input="onInput"
                           @focus="open = query.length > 0"
                           @keydown.escape="open = false"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="selectHighlighted()"
                           @blur="onBlur"
                           placeholder="Name suchen..."
                           autocomplete="off"
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" x-show="selectedId" @click="clear()"
                            class="absolute right-2 top-8 text-gray-400 hover:text-gray-600 text-xs">✕</button>

                    <div x-show="open && results.length > 0"
                         class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-52 overflow-auto text-sm">
                        <template x-for="(item, i) in results" :key="item.id">
                            <div @mousedown.prevent="selectById(item)"
                                 :class="i === highlighted ? 'bg-indigo-50 text-indigo-800' : 'text-gray-800 hover:bg-gray-50'"
                                 class="px-3 py-2 cursor-pointer">
                                <span x-text="item.anzeigename" class="font-medium"></span>
                                <span x-show="item.email" x-text="' (' + item.email + ')'" class="text-gray-400 text-xs"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Schutzbedarf --}}
                <div x-data="{
                        origC: '{{ $app->confidentiality }}',
                        origI: '{{ $app->integrity }}',
                        origA: '{{ $app->availability }}',
                        valC: '{{ old('confidentiality', $app->confidentiality) }}',
                        valI: '{{ old('integrity', $app->integrity) }}',
                        valA: '{{ old('availability', $app->availability) }}',
                        get changed() { return this.valC !== this.origC || this.valI !== this.origI || this.valA !== this.origA; }
                    }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schutzbedarf</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['confidentiality' => 'Vertraulichkeit (C)', 'integrity' => 'Integrität (I)', 'availability' => 'Verfügbarkeit (V)'] as $field => $label)
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                            <select name="{{ $field }}"
                                    x-model="{{ $field === 'confidentiality' ? 'valC' : ($field === 'integrity' ? 'valI' : 'valA') }}"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(['A' => 'A – Normal', 'B' => 'B – Hoch', 'C' => 'C – Sehr hoch'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old($field, $app->$field) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endforeach
                    </div>

                    <div x-show="changed" x-cloak class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Begründung für Schutzbedarf-Änderung <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" rows="2" :required="changed"
                                  class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ $errors->has('reason') ? 'border-red-400' : '' }}">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Allgemeiner Kommentar --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Allgemeiner Kommentar
                        <span class="text-xs font-normal text-gray-400 ml-1">(optional)</span>
                    </label>
                    <textarea name="kommentar" rows="3"
                              placeholder="Anmerkungen, Hinweise oder sonstige Rückmeldungen zur Applikation..."
                              class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('kommentar') }}</textarea>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <button type="button" @click="mode = 'view'"
                            class="text-sm text-gray-500 hover:text-gray-700 underline">
                        Abbrechen
                    </button>
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 text-sm transition">
                        Rückmeldung senden &amp; Weiter →
                    </button>
                </div>
            </form>
        </div>

    </div>

    <p class="text-center text-xs text-gray-400 mt-5">IT Cockpit &middot; automatisch generiert</p>
</div>

<script>
var adUsers = @json($adUsers->map(fn($u) => ['id' => $u->id, 'anzeigename' => $u->anzeigename, 'email' => $u->email ?? '']));

function adSearch(fieldName, initialValue, users, initialId = null, initialLabel = '') {
    return {
        query: initialId ? initialLabel : (initialValue || ''),
        value: initialValue || '',
        selectedId: initialId,
        open: false,
        highlighted: -1,
        results: [],
        onInput() {
            this.value = this.query;
            this.selectedId = null;
            var q = this.query.toLowerCase().trim();
            if (q.length < 1) { this.open = false; this.results = []; return; }
            this.results = users.filter(u => u.anzeigename.toLowerCase().includes(q) || (u.email && u.email.toLowerCase().includes(q))).slice(0, 20);
            this.highlighted = -1;
            this.open = this.results.length > 0;
        },
        select(item) {
            this.query = item.anzeigename;
            this.value = item.anzeigename;
            this.selectedId = null;
            this.open = false;
        },
        selectById(item) {
            this.query = item.anzeigename;
            this.selectedId = item.id;
            this.open = false;
        },
        selectHighlighted() {
            if (this.highlighted >= 0 && this.results[this.highlighted]) {
                if (this.selectedId !== undefined) this.selectById(this.results[this.highlighted]);
                else this.select(this.results[this.highlighted]);
            }
        },
        clear() {
            this.query = '';
            this.value = '';
            this.selectedId = null;
        },
        move(dir) {
            this.highlighted = Math.max(-1, Math.min(this.results.length - 1, this.highlighted + dir));
        },
        onBlur() {
            setTimeout(() => { this.open = false; }, 150);
        }
    };
}
</script>
</body>
</html>
