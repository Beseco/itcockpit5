@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>
    .EasyMDEContainer .CodeMirror { min-height: 300px; font-size: 14px; }
    .editor-toolbar { border-radius: 0; }
    .EasyMDEContainer { border-radius: 0.375rem; overflow: hidden; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
    window._easyMDE = null;
</script>
@endpush

<div class="p-6 space-y-6"
     x-data="{
         arbeitsvorgaenge: {{ Js::from(
             isset($stelle) && $stelle !== null
                 ? $stelle->arbeitsvorgaenge->map(fn($av) => [
                     'betreff'      => $av->betreff,
                     'beschreibung' => $av->beschreibung ?? '',
                     'anteil'       => $av->anteil,
                 ])->values()->toArray()
                 : []
         ) }},

         editorOpen: false,
         editorIndex: null,
         editorBetreff: '',

         get totalAnteil() {
             return this.arbeitsvorgaenge.reduce((s, av) => s + (parseInt(av.anteil) || 0), 0);
         },

         addAv() {
             this.arbeitsvorgaenge.push({ betreff: '', beschreibung: '', anteil: 0 });
         },

         removeAv(index) {
             this.arbeitsvorgaenge.splice(index, 1);
         },

         openEditor(index) {
             this.editorIndex   = index;
             this.editorBetreff = this.arbeitsvorgaenge[index].betreff;
             this.editorOpen    = true;
             this.$nextTick(() => {
                 if (window._easyMDE) {
                     window._easyMDE.toTextArea();
                     window._easyMDE = null;
                 }
                 window._easyMDE = new EasyMDE({
                     element: document.getElementById('mde-beschreibung'),
                     initialValue: this.arbeitsvorgaenge[index].beschreibung || '',
                     spellChecker: false,
                     autofocus: true,
                     placeholder: 'Beschreibung des Arbeitsvorgangs...',
                     toolbar: [
                         'bold', 'italic', '|',
                         'unordered-list', 'ordered-list', '|',
                         'heading-2', 'heading-3', '|',
                         'preview', 'side-by-side', 'fullscreen'
                     ],
                 });
             });
         },

         saveEditor() {
             if (window._easyMDE && this.editorIndex !== null) {
                 this.arbeitsvorgaenge[this.editorIndex].beschreibung = window._easyMDE.value();
             }
             this.closeEditor();
         },

         closeEditor() {
             this.editorOpen  = false;
             this.editorIndex = null;
             if (window._easyMDE) {
                 window._easyMDE.toTextArea();
                 window._easyMDE = null;
             }
         }
     }">

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Bezeichnung --}}
        <div class="md:col-span-2">
            <x-input-label for="bezeichnung" value="Stellenbezeichnung *" />
            <x-text-input id="bezeichnung" name="bezeichnung" type="text" class="mt-1 block w-full"
                value="{{ old('bezeichnung', $stelle?->bezeichnung) }}" required autofocus />
            <x-input-error :messages="$errors->get('bezeichnung')" class="mt-1" />
        </div>

        {{-- Stellennummer --}}
        <div>
            <x-input-label for="stellennummer" value="Stellennummer" />
            <x-text-input id="stellennummer" name="stellennummer" type="text" class="mt-1 block w-full"
                value="{{ old('stellennummer', $stelle?->stellennummer) }}"
                placeholder="z.B. 14.1-001" />
            <x-input-error :messages="$errors->get('stellennummer')" class="mt-1" />
        </div>

        {{-- Gruppe --}}
        <div>
            <x-input-label for="gruppe_id" value="Gruppe" />
            <select id="gruppe_id" name="gruppe_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">— Keine Gruppe —</option>
                @foreach($gruppen as $g)
                    <option value="{{ $g->id }}" @selected(old('gruppe_id', $stelle?->gruppe_id) == $g->id)>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- TVöD --}}
        <div>
            <x-input-label for="tvod_bewertung" value="TVöD-Bewertung" />
            <select id="tvod_bewertung" name="tvod_bewertung"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">— Keine Angabe —</option>
                <optgroup label="Entgeltgruppen">
                    @foreach(\App\Models\Stelle::TVOD as $tvod)
                        @if(str_starts_with($tvod, 'EG'))
                            <option value="{{ $tvod }}" @selected(old('tvod_bewertung', $stelle?->tvod_bewertung) === $tvod)>{{ $tvod }}</option>
                        @endif
                    @endforeach
                </optgroup>
                <optgroup label="Sozial- und Erziehungsdienst">
                    @foreach(\App\Models\Stelle::TVOD as $tvod)
                        @if(str_starts_with($tvod, 'S'))
                            <option value="{{ $tvod }}" @selected(old('tvod_bewertung', $stelle?->tvod_bewertung) === $tvod)>{{ $tvod }}</option>
                        @endif
                    @endforeach
                </optgroup>
            </select>
        </div>

        {{-- Stunden --}}
        <div>
            <x-input-label for="stunden" value="Wochenstunden" />
            <x-text-input id="stunden" name="stunden" type="number" step="0.5" min="0" max="50"
                class="mt-1 block w-full"
                value="{{ old('stunden', $stelle?->stunden) }}"
                placeholder="z.B. 39.5" />
            <p class="text-xs text-gray-500 mt-1">≥ 39 Std. = Vollzeit</p>
        </div>

        {{-- Stelleninhaber --}}
        <div x-data="{
                 open: false,
                 search: '{{ isset($stelle) && $stelle?->stelleninhaber ? $stelle->stelleninhaber->name : '' }}',
                 selectedId: '{{ old('user_id', $stelle?->user_id ?? '') }}',
                 users: {{ Js::from($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) }},
                 get filtered() {
                     if (!this.search) return this.users;
                     const q = this.search.toLowerCase();
                     return this.users.filter(u => u.name.toLowerCase().includes(q));
                 },
                 select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                 clear() { this.search = ''; this.selectedId = ''; }
             }">
            <x-input-label value="Stelleninhaber (optional)" />
            <div class="relative mt-1">
                <input type="hidden" name="user_id" :value="selectedId">
                <input type="text" x-model="search" @focus="open = true" @input="open = true"
                       @keydown.escape="open = false"
                       placeholder="Name suchen oder leer lassen (unbesetzt)..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <button type="button" x-show="selectedId" @click="clear()"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
                <div x-show="open && filtered.length > 0" x-cloak @click.outside="open = false"
                     class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto">
                    <div class="py-1 px-3 text-xs text-gray-500 border-b cursor-pointer hover:bg-gray-50"
                         @click="clear(); open = false">— Stelle ist unbesetzt —</div>
                    <template x-for="u in filtered" :key="u.id">
                        <div @click="select(u)" x-text="u.name"
                             class="py-2 px-3 text-sm text-gray-700 hover:bg-indigo-50 cursor-pointer"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Arbeitsvorgänge --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <x-input-label value="Stellenbeschreibung (Arbeitsvorgänge)" />
            <span class="text-sm font-semibold px-2 py-0.5 rounded"
                  :class="totalAnteil === 100 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'">
                Gesamt: <span x-text="totalAnteil"></span>%
                <span x-show="totalAnteil !== 100"> ⚠️</span>
            </span>
        </div>

        <div class="border border-gray-200 rounded-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-8">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-2/5">Betreff</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Anteil %</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(av, index) in arbeitsvorgaenge" :key="index">
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-gray-400 align-top" x-text="index + 1"></td>
                            <td class="px-3 py-2 align-top">
                                <input type="text"
                                       :name="'arbeitsvorgaenge[' + index + '][betreff]'"
                                       x-model="av.betreff"
                                       placeholder="z.B. AV1: Benutzerverwaltung"
                                       class="block w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       required />
                            </td>
                            <td class="px-3 py-2 align-top">
                                {{-- Hidden textarea carries the value on form submit --}}
                                <textarea :name="'arbeitsvorgaenge[' + index + '][beschreibung]'"
                                          x-model="av.beschreibung"
                                          class="hidden"></textarea>

                                {{-- Preview + edit button --}}
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-600 line-clamp-2"
                                           x-show="av.beschreibung"
                                           x-text="av.beschreibung.replace(/[#*_`>[\]]/g, '').substring(0, 150) + (av.beschreibung.length > 150 ? '…' : '')">
                                        </p>
                                        <p class="text-xs text-gray-400 italic" x-show="!av.beschreibung">
                                            Keine Beschreibung
                                        </p>
                                    </div>
                                    <button type="button"
                                            @click="openEditor(index)"
                                            class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-1 text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded whitespace-nowrap">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Bearbeiten
                                    </button>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input type="number"
                                       :name="'arbeitsvorgaenge[' + index + '][anteil]'"
                                       x-model="av.anteil"
                                       min="0" max="100"
                                       class="block w-full border-gray-300 rounded text-sm focus:ring-indigo-500 focus:border-indigo-500 text-center" />
                            </td>
                            <td class="px-3 py-2 text-center align-top pt-2">
                                <button type="button" @click="removeAv(index)"
                                        class="text-red-400 hover:text-red-600 font-bold text-xl leading-none">×</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="arbeitsvorgaenge.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-gray-400 text-sm">
                            Noch keine Arbeitsvorgänge. Klicken Sie auf "+ Hinzufügen".
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                <button type="button" @click="addAv()"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    + Arbeitsvorgang hinzufügen
                </button>
            </div>
        </div>
    </div>

    {{-- EasyMDE Modal --}}
    <div x-show="editorOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl flex flex-col"
             style="max-height: 90vh;" @click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Beschreibung bearbeiten</h3>
                    <p class="text-sm text-gray-500 mt-0.5" x-text="'Arbeitsvorgang: ' + editorBetreff"></p>
                </div>
                <button type="button" @click="closeEditor()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-auto px-6 py-4 min-h-0">
                <textarea id="mde-beschreibung"></textarea>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 flex-shrink-0 rounded-b-lg">
                <p class="text-xs text-gray-500">
                    Unterstützt Markdown: **fett**, *kursiv*, Aufzählungen (·), Überschriften (#)
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="closeEditor()"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Abbrechen
                    </button>
                    <button type="button" @click="saveEditor()"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700 font-medium">
                        Übernehmen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
        <a href="{{ route('stellen.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Abbrechen</a>
        <x-primary-button>Speichern</x-primary-button>
    </div>
</div>
