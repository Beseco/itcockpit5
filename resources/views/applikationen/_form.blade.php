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
                <textarea id="einsatzzweck" name="einsatzzweck" rows="6"
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

            {{-- Hersteller / Lieferant --}}
            <div>
                <x-input-label for="hersteller" value="Hersteller / Lieferant" />
                @php
                    $currentHersteller = old('hersteller', $app->hersteller ?? '');
                    $herstellerInList  = $vendors->pluck('firmenname')->contains($currentHersteller);
                @endphp
                <select id="hersteller" name="hersteller"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— kein / unbekannt —</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->firmenname }}" @selected($currentHersteller === $v->firmenname)>
                            {{ $v->firmenname }}
                        </option>
                    @endforeach
                    @if($currentHersteller && !$herstellerInList)
                        <option value="{{ $currentHersteller }}" selected>
                            {{ $currentHersteller }} (nicht in Liste)
                        </option>
                    @endif
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Nicht dabei?
                    <a href="{{ route('dienstleister.create') }}" target="_blank"
                       class="text-indigo-600 hover:text-indigo-800 underline">
                        Neuen Dienstleister anlegen ↗
                    </a>
                    – danach diese Seite neu laden.
                </p>
                <x-input-error :messages="$errors->get('hersteller')" class="mt-2" />
            </div>

            {{-- Ansprechpartner (beim Lieferanten) --}}
            <div>
                <x-input-label for="ansprechpartner" value="Ansprechpartner (beim Lieferanten)" />
                <x-text-input id="ansprechpartner" name="ansprechpartner" type="text" class="mt-1 block w-full"
                              value="{{ old('ansprechpartner', $app->ansprechpartner ?? '') }}" />
                <x-input-error :messages="$errors->get('ansprechpartner')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="doc_url" value="Dokumentations-URL" />
                <x-text-input id="doc_url" name="doc_url" type="text" class="mt-1 block w-full"
                              placeholder="https://..." value="{{ old('doc_url', $app->doc_url ?? '') }}" />
                <x-input-error :messages="$errors->get('doc_url')" class="mt-2" />
            </div>


        </div>
    </div>

    {{-- Zuständigkeiten --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">Zuständigkeiten</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <x-input-label for="abteilung_id" value="Sachgebiet / Abteilung" />
                <select id="abteilung_id" name="abteilung_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">— keine Zuordnung —</option>
                    @foreach($abteilungen as $abt)
                        <option value="{{ $abt->id }}"
                            {{ old('abteilung_id', $app?->abteilung_id ?? '') == $abt->id ? 'selected' : '' }}>
                            {{ $abt->anzeigename }}
                        </option>
                    @endforeach
                </select>
                {{-- Legacy-Hinweis: alter Textwert ohne Datenbankzuordnung --}}
                @if(isset($app) && $app->sg && !$app->abteilung_id)
                    <p class="mt-1 text-xs text-amber-600">
                        Bisheriger Eintrag (nicht zugeordnet): <span class="font-medium">{{ $app->sg }}</span>
                    </p>
                    <input type="hidden" name="sg" value="{{ $app->sg }}">
                @endif
                <x-input-error :messages="$errors->get('abteilung_id')" class="mt-2" />
            </div>

            {{-- Verfahrensverantwortlicher (AdUser-Picker) --}}
            <div x-data="{
                    open: false,
                    search: '{{ old('verantwortlich_ad_user_id') ? ($adUsers->firstWhere('id', old('verantwortlich_ad_user_id'))?->anzeigenameOrName ?? '') : ($app?->verantwortlichAdUser?->anzeigenameOrName ?? '') }}',
                    selectedId: '{{ old('verantwortlich_ad_user_id', $app?->verantwortlich_ad_user_id ?? '') }}',
                    users: {{ Js::from($adUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->anzeigenameOrName])) }},
                    get filtered() {
                        if (!this.search) return this.users;
                        const q = this.search.toLowerCase();
                        return this.users.filter(u => u.name.toLowerCase().includes(q));
                    },
                    select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                    clear() { this.search = ''; this.selectedId = ''; }
                }" @click.outside="open = false">
                <x-input-label for="verantwortlich_ad_user_id" value="Verfahrensverantwortlicher" />
                <input type="hidden" name="verantwortlich_ad_user_id" :value="selectedId">
                <div class="relative mt-1">
                    <input type="text" id="verantwortlich_ad_user_id"
                           x-model="search" @focus="open = true" @input="open = true" @keydown.escape="open = false"
                           placeholder="AD-Benutzer suchen…" autocomplete="off"
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
                {{-- Legacy-Hinweis: alter Textwert ohne AD-Zuordnung --}}
                @if(isset($app) && $app->verantwortlich_sg && !$app->verantwortlich_ad_user_id)
                    <p class="mt-1 text-xs text-amber-600">
                        Bisheriger Eintrag (nicht zugeordnet): <span class="font-medium">{{ $app->verantwortlich_sg }}</span>
                    </p>
                    <input type="hidden" name="verantwortlich_sg" value="{{ $app->verantwortlich_sg }}">
                @endif
                <x-input-error :messages="$errors->get('verantwortlich_ad_user_id')" class="mt-2" />
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
                {{-- Legacy-Hinweis: alter Textwert ohne Benutzer-Zuordnung --}}
                @if(isset($app) && $app->admin && !$app->admin_user_id)
                    <p class="mt-1 text-xs text-amber-600">
                        Bisheriger Eintrag (nicht zugeordnet): <span class="font-medium">{{ $app->admin }}</span>
                    </p>
                @endif
                <x-input-error :messages="$errors->get('admin_user_id')" class="mt-2" />
            </div>

        </div>
    </div>

    {{-- Verknüpfte Server --}}
    @if (isset($servers) && $servers->isNotEmpty())
    <div>
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3 pb-1 border-b border-gray-200">
            Verknüpfte Server
        </h3>
        @php
            $selectedServerIds = old('server_ids', isset($app) ? $app->servers?->pluck('id')->toArray() ?? [] : []);
        @endphp
        <div x-data="{ search: '' }">
            <input type="text" x-model="search" placeholder="Server filtern…"
                   class="mb-2 block w-72 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-100">
                @foreach ($servers as $srv)
                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                           x-show="!search || '{{ strtolower($srv->name) }}'.includes(search.toLowerCase())">
                        <input type="checkbox" name="server_ids[]" value="{{ $srv->id }}"
                               @checked(in_array($srv->id, $selectedServerIds))
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-800">{{ $srv->name }}</span>
                        @if ($srv->dns_hostname)
                            <span class="text-xs text-gray-400">{{ $srv->dns_hostname }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>
        <x-input-error :messages="$errors->get('server_ids')" class="mt-2" />
    </div>
    @endif

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
