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

    @once
    <script>
    function adUserSearch(fieldName, initialId, initialLabel) {
        return {
            query: initialLabel || '',
            selectedId: initialId || null,
            open: false,
            highlighted: -1,
            results: [],
            get users() { return window._adUsers || []; },
            onInput() {
                this.selectedId = null;
                var q = this.query.toLowerCase().trim();
                if (q.length < 1) { this.open = false; this.results = []; return; }
                this.results = this.users.filter(u =>
                    u.anzeigename.toLowerCase().includes(q) ||
                    (u.email && u.email.toLowerCase().includes(q))
                ).slice(0, 20);
                this.highlighted = -1;
                this.open = this.results.length > 0;
            },
            select(item) {
                this.query = item.anzeigename;
                this.selectedId = item.id;
                this.open = false;
            },
            clear() { this.query = ''; this.selectedId = null; this.open = false; },
            move(dir) {
                this.highlighted = Math.max(-1, Math.min(this.results.length - 1, this.highlighted + dir));
            },
            selectHighlighted() {
                if (this.highlighted >= 0 && this.results[this.highlighted]) {
                    this.select(this.results[this.highlighted]);
                }
            },
            onBlur() { setTimeout(() => { this.open = false; }, 150); },
        };
    }
    </script>
    <script>
    window._adUsers = @json($adUsers->map(fn($u) => ['id' => $u->id, 'anzeigename' => $u->anzeigename, 'email' => $u->email ?? '']));
    </script>
    @endonce

    {{-- Trennlinie --}}
    <div class="border-t border-gray-200 pt-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Verantwortliche &amp; Revision</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Vorgesetzter --}}
            <div x-data="adUserSearch(
                    'vorgesetzter_ad_user_id',
                    {{ old('vorgesetzter_ad_user_id', $abteilung->vorgesetzter_ad_user_id ?? 'null') }},
                    {{ json_encode(old('vorgesetzter_ad_user_id') ? ($adUsers->find(old('vorgesetzter_ad_user_id'))?->anzeigename ?? '') : ($abteilung->vorgesetzter?->anzeigename ?? '')) }}
                 )" class="relative">
                <x-input-label value="Vorgesetzter (aus AD)" />
                <input type="hidden" name="vorgesetzter_ad_user_id" :value="selectedId">
                <div class="relative mt-1">
                    <input type="text"
                           x-model="query"
                           @input="onInput"
                           @focus="open = query.length > 0"
                           @keydown.escape="open = false"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="selectHighlighted()"
                           @blur="onBlur"
                           placeholder="Name suchen…"
                           autocomplete="off"
                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <button type="button" x-show="selectedId" @click="clear()"
                            class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 text-xs">✕</button>
                </div>
                <div x-show="open && results.length > 0" x-cloak
                     class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-52 overflow-auto text-sm">
                    <template x-for="(item, i) in results" :key="item.id">
                        <div @mousedown.prevent="select(item)"
                             :class="i === highlighted ? 'bg-indigo-50 text-indigo-800' : 'text-gray-800 hover:bg-gray-50'"
                             class="px-3 py-2 cursor-pointer">
                            <span x-text="item.anzeigename" class="font-medium"></span>
                            <span x-show="item.email" x-text="' (' + item.email + ')'" class="text-gray-400 text-xs ml-1"></span>
                        </div>
                    </template>
                </div>
                <x-input-error :messages="$errors->get('vorgesetzter_ad_user_id')" class="mt-2" />
            </div>

            {{-- Stellvertreter --}}
            <div x-data="adUserSearch(
                    'stellvertreter_ad_user_id',
                    {{ old('stellvertreter_ad_user_id', $abteilung->stellvertreter_ad_user_id ?? 'null') }},
                    {{ json_encode(old('stellvertreter_ad_user_id') ? ($adUsers->find(old('stellvertreter_ad_user_id'))?->anzeigename ?? '') : ($abteilung->stellvertreter?->anzeigename ?? '')) }}
                 )" class="relative">
                <x-input-label value="Stellvertreter (aus AD)" />
                <input type="hidden" name="stellvertreter_ad_user_id" :value="selectedId">
                <div class="relative mt-1">
                    <input type="text"
                           x-model="query"
                           @input="onInput"
                           @focus="open = query.length > 0"
                           @keydown.escape="open = false"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="selectHighlighted()"
                           @blur="onBlur"
                           placeholder="Name suchen…"
                           autocomplete="off"
                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <button type="button" x-show="selectedId" @click="clear()"
                            class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 text-xs">✕</button>
                </div>
                <div x-show="open && results.length > 0" x-cloak
                     class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-52 overflow-auto text-sm">
                    <template x-for="(item, i) in results" :key="item.id">
                        <div @mousedown.prevent="select(item)"
                             :class="i === highlighted ? 'bg-indigo-50 text-indigo-800' : 'text-gray-800 hover:bg-gray-50'"
                             class="px-3 py-2 cursor-pointer">
                            <span x-text="item.anzeigename" class="font-medium"></span>
                            <span x-show="item.email" x-text="' (' + item.email + ')'" class="text-gray-400 text-xs ml-1"></span>
                        </div>
                    </template>
                </div>
                <x-input-error :messages="$errors->get('stellvertreter_ad_user_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="revision_date" value="Revisionsdatum" />
                <x-text-input id="revision_date" name="revision_date" type="date" class="mt-1 block w-full"
                              value="{{ old('revision_date', isset($abteilung->revision_date) ? $abteilung->revision_date->format('Y-m-d') : '') }}" />
                <x-input-error :messages="$errors->get('revision_date')" class="mt-2" />
            </div>

        </div>
    </div>

</div>
