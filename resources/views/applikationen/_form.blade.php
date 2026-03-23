{{-- Gemeinsames Formular für create und edit --}}

<div class="space-y-6">

    {{-- Stammdaten --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">Stammdaten</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="sm:col-span-2">
                <x-input-label for="name" value="Name der Applikation *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              value="{{ old('name', $app->name ?? '') }}" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="einsatzzweck" value="Einsatzzweck / Beschreibung" />
                <textarea id="einsatzzweck" name="einsatzzweck" rows="3"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('einsatzzweck', $app->einsatzzweck ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('einsatzzweck')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="baustein" value="Baustein / Typ" />
                <select id="baustein" name="baustein"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">– Bitte wählen –</option>
                    @foreach ($bausteine as $key => $label)
                        <option value="{{ $key }}" {{ old('baustein', $app->baustein ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('baustein')" class="mt-2" />
            </div>

            {{-- Hersteller Autocomplete --}}
            <div x-data="{
                    open: false,
                    search: '{{ old('hersteller', $app->hersteller ?? '') }}',
                    vendors: {{ Js::from($vendors->map(fn($v) => $v->firmenname)) }},
                    get filtered() {
                        if (this.search.trim() === '') return this.vendors;
                        return this.vendors.filter(v => v.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    select(name) { this.search = name; this.open = false; }
                }" @click.outside="open = false">
                <x-input-label for="hersteller" value="Hersteller / Lieferant" />
                <div class="relative mt-1">
                    <input id="hersteller" name="hersteller" type="text"
                           x-model="search" @focus="open = true" @input="open = true"
                           @keydown.escape="open = false"
                           placeholder="Hersteller suchen..."
                           autocomplete="off"
                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <ul x-show="open && filtered.length > 0" x-cloak
                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto">
                        <template x-for="vendor in filtered" :key="vendor">
                            <li>
                                <button type="button" @click="select(vendor)"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
                                        x-text="vendor"></button>
                            </li>
                        </template>
                    </ul>
                </div>
                <x-input-error :messages="$errors->get('hersteller')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="doc_url" value="Dokumentations-URL" />
                <x-text-input id="doc_url" name="doc_url" type="text" class="mt-1 block w-full"
                              placeholder="https://..." value="{{ old('doc_url', $app->doc_url ?? '') }}" />
                <x-input-error :messages="$errors->get('doc_url')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="revision_date" value="Nächste Revision" />
                <x-text-input id="revision_date" name="revision_date" type="date" class="mt-1 block w-full"
                              value="{{ old('revision_date', isset($app) && $app->revision_date ? $app->revision_date->format('Y-m-d') : '') }}" />
                <x-input-error :messages="$errors->get('revision_date')" class="mt-2" />
            </div>

        </div>
    </div>

    {{-- Zuständigkeiten --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">Zuständigkeiten</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <x-input-label for="sg" value="Sachgebiet / Abteilung" />
                <x-text-input id="sg" name="sg" type="text" class="mt-1 block w-full"
                              value="{{ old('sg', $app->sg ?? '') }}" />
                <x-input-error :messages="$errors->get('sg')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="verantwortlich_sg" value="Verfahrensverantwortlicher" />
                <x-text-input id="verantwortlich_sg" name="verantwortlich_sg" type="text" class="mt-1 block w-full"
                              value="{{ old('verantwortlich_sg', $app->verantwortlich_sg ?? '') }}" />
                <x-input-error :messages="$errors->get('verantwortlich_sg')" class="mt-2" />
            </div>

            {{-- IT-Administrator (User-Picker) --}}
            <div x-data="{
                    open: false,
                    search: '{{ old('admin_user_id') ? ($users->firstWhere('id', old('admin_user_id'))?->name ?? '') : ($app?->adminUser?->name ?? '') }}',
                    selectedId: '{{ old('admin_user_id', $app?->admin_user_id ?? '') }}',
                    users: {{ Js::from($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])) }},
                    get filtered() {
                        if (!this.search) return this.users;
                        const q = this.search.toLowerCase();
                        return this.users.filter(u => u.name.toLowerCase().includes(q));
                    },
                    select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                    clear() { this.search = ''; this.selectedId = ''; }
                }" @click.outside="open = false">
                <x-input-label for="admin_user_id" value="IT-Administrator" />
                <input type="hidden" name="admin_user_id" :value="selectedId">
                <div class="relative mt-1">
                    <input type="text" id="admin_user_id"
                           x-model="search" @focus="open = true" @input="open = true" @keydown.escape="open = false"
                           placeholder="Benutzer suchen…" autocomplete="off"
                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <button x-show="selectedId" type="button" @click="clear()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <ul x-show="open && filtered.length > 0" x-cloak
                        class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto">
                        <template x-for="u in filtered" :key="u.id">
                            <li>
                                <button type="button" @click="select(u)"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
                                        x-text="u.name"></button>
                            </li>
                        </template>
                    </ul>
                </div>
                <x-input-error :messages="$errors->get('admin_user_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ansprechpartner" value="Ansprechpartner" />
                <x-text-input id="ansprechpartner" name="ansprechpartner" type="text" class="mt-1 block w-full"
                              value="{{ old('ansprechpartner', $app->ansprechpartner ?? '') }}" />
                <x-input-error :messages="$errors->get('ansprechpartner')" class="mt-2" />
            </div>

        </div>
    </div>

    {{-- Schutzbedarf (BSI) --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">
            Schutzbedarf (BSI-Grundschutz)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ([
                'confidentiality' => 'Vertraulichkeit',
                'integrity'       => 'Integrität',
                'availability'    => 'Verfügbarkeit',
            ] as $field => $label)
            <div>
                <x-input-label :value="$label . ' *'" />
                <select name="{{ $field }}"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    @foreach ($schutzbedarf as $key => $text)
                        <option value="{{ $key }}"
                            {{ old($field, isset($app) ? $app->$field : 'A') === $key ? 'selected' : '' }}>
                            {{ $key }} – {{ $text }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get($field)" class="mt-2" />
            </div>
            @endforeach
        </div>
    </div>

</div>
