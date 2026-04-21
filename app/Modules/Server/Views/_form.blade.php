{{-- Gemeinsames Formular für Create und Edit --}}
{{-- Variablen: $server (null bei Create), $abteilungen, $users, $gruppen, $applikationen,
               $osTypes, $roles, $backupLevels, $patchRings, $statusOptions, $typeOptions --}}

<div class="space-y-6">

    {{-- ── Stammdaten ──────────────────────────────────────────────────────── --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 pb-1 border-b border-gray-200">
            Stammdaten
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div class="md:col-span-2">
                <x-input-label for="name" value="Name *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              value="{{ old('name', $server?->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="dns_hostname" value="DNS-Hostname" />
                <x-text-input id="dns_hostname" name="dns_hostname" type="text" class="mt-1 block w-full"
                              value="{{ old('dns_hostname', $server?->dns_hostname) }}" />
                <x-input-error :messages="$errors->get('dns_hostname')" class="mt-1" />
            </div>

            @if(\App\Modules\Server\Models\CheckMkSettings::getSingleton()->enabled)
            <div>
                <x-input-label for="checkmk_alias" value="CheckMK Hostname (optional)" />
                <x-text-input id="checkmk_alias" name="checkmk_alias" type="text" class="mt-1 block w-full"
                              placeholder="Leer = DNS-Hostname wird verwendet"
                              value="{{ old('checkmk_alias', $server?->checkmk_alias) }}" />
                <p class="mt-1 text-xs text-gray-400">Nur ausfüllen wenn der Hostname in CheckMK vom DNS-Hostname abweicht.</p>
                <x-input-error :messages="$errors->get('checkmk_alias')" class="mt-1" />
            </div>
            @endif

            <div>
                <x-input-label for="ip_address" value="IP-Adresse" />
                <x-text-input id="ip_address" name="ip_address" type="text" class="mt-1 block w-full"
                              value="{{ old('ip_address', $server?->ip_address) }}" placeholder="z.B. 192.168.1.10" />
                <x-input-error :messages="$errors->get('ip_address')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="operating_system" value="Betriebssystem (Freitext)" />
                <x-text-input id="operating_system" name="operating_system" type="text" class="mt-1 block w-full"
                              value="{{ old('operating_system', $server?->operating_system) }}" placeholder="z.B. Windows Server 2022" />
                <x-input-error :messages="$errors->get('operating_system')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="os_version" value="OS-Version" />
                <x-text-input id="os_version" name="os_version" type="text" class="mt-1 block w-full"
                              value="{{ old('os_version', $server?->os_version) }}" />
                <x-input-error :messages="$errors->get('os_version')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="doc_url" value="Dokumentations-Link" />
                <x-text-input id="doc_url" name="doc_url" type="url" class="mt-1 block w-full"
                              value="{{ old('doc_url', $server?->doc_url) }}" placeholder="https://…" />
                <x-input-error :messages="$errors->get('doc_url')" class="mt-1" />
            </div>


            <div class="md:col-span-2">
                <x-input-label for="description" value="Beschreibung" />
                <textarea id="description" name="description" rows="2"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('description', $server?->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="bemerkungen" value="Bemerkungen" />
                <textarea id="bemerkungen" name="bemerkungen" rows="2"
                          class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('bemerkungen', $server?->bemerkungen) }}</textarea>
                <x-input-error :messages="$errors->get('bemerkungen')" class="mt-1" />
            </div>

        </div>
    </div>

    {{-- ── Klassifizierung ─────────────────────────────────────────────────── --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 pb-1 border-b border-gray-200">
            Klassifizierung
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <x-input-label for="status" value="Status *" />
                <select id="status" name="status"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach ($statusOptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', $server?->status ?? 'produktiv') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="type" value="Typ (VM / Bare Metal)" />
                <select id="type" name="type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht angegeben —</option>
                    @foreach ($typeOptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', $server?->type) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="os_type_id" value="OS-Typ" />
                <select id="os_type_id" name="os_type_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht angegeben —</option>
                    @foreach ($osTypes as $opt)
                        <option value="{{ $opt->id }}" @selected(old('os_type_id', $server?->os_type_id) == $opt->id)>{{ $opt->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('os_type_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="role_id" value="Rolle" />
                <select id="role_id" name="role_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht angegeben —</option>
                    @foreach ($roles as $opt)
                        <option value="{{ $opt->id }}" @selected(old('role_id', $server?->role_id) == $opt->id)>{{ $opt->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="backup_level_id" value="Backup-Stufe" />
                <select id="backup_level_id" name="backup_level_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht angegeben —</option>
                    @foreach ($backupLevels as $opt)
                        <option value="{{ $opt->id }}" @selected(old('backup_level_id', $server?->backup_level_id) == $opt->id)>{{ $opt->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('backup_level_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="patch_ring_id" value="Patch-Ring" />
                <select id="patch_ring_id" name="patch_ring_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht angegeben —</option>
                    @foreach ($patchRings as $opt)
                        <option value="{{ $opt->id }}" @selected(old('patch_ring_id', $server?->patch_ring_id) == $opt->id)>{{ $opt->label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('patch_ring_id')" class="mt-1" />
            </div>

        </div>
    </div>

    {{-- ── Zuständigkeiten ─────────────────────────────────────────────────── --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 pb-1 border-b border-gray-200">
            Zuständigkeiten
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <x-input-label for="abteilung_id" value="Abteilung" />
                <select id="abteilung_id" name="abteilung_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Keine Zuordnung —</option>
                    @foreach ($abteilungen as $abt)
                        <option value="{{ $abt->id }}" @selected(old('abteilung_id', $server?->abteilung_id) == $abt->id)>
                            {{ $abt->anzeigename }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('abteilung_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="admin_user_id" value="Verantwortlicher Admin" />
                <select id="admin_user_id" name="admin_user_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Nicht zugeordnet —</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('admin_user_id', $server?->admin_user_id) == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('admin_user_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="gruppe_id" value="Gruppe" />
                <select id="gruppe_id" name="gruppe_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">— Keine Zuordnung —</option>
                    @foreach ($gruppen as $gruppe)
                        <option value="{{ $gruppe->id }}" @selected(old('gruppe_id', $server?->gruppe_id) == $gruppe->id)>
                            {{ $gruppe->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('gruppe_id')" class="mt-1" />
            </div>

        </div>
    </div>

    {{-- ── Verknüpfte Applikationen ─────────────────────────────────────────── --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 pb-1 border-b border-gray-200">
            Verknüpfte Applikationen
        </h3>
        @php
            $selectedAppIds = old('applikation_ids', $server?->applikationen?->pluck('id')->toArray() ?? []);
        @endphp
        <div x-data="{ search: '' }">
            <input type="text" x-model="search" placeholder="Applikation filtern…"
                   class="mb-2 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <div class="max-h-52 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-100">
                @forelse ($applikationen as $app)
                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer"
                           x-show="!search || '{{ strtolower($app->name) }}'.includes(search.toLowerCase())">
                        <input type="checkbox" name="applikation_ids[]" value="{{ $app->id }}"
                               @checked(in_array($app->id, $selectedAppIds))
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-800">{{ $app->name }}</span>
                    </label>
                @empty
                    <p class="px-3 py-2 text-sm text-gray-400">Keine Applikationen vorhanden.</p>
                @endforelse
            </div>
        </div>
        <x-input-error :messages="$errors->get('applikation_ids')" class="mt-2" />
    </div>

</div>
