<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Backup – Einstellungen</h2>
            <a href="{{ route('backup.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Zur Übersicht</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-5">Backup-Konfiguration</h3>

                <form method="POST" action="{{ route('backup.settings.update') }}" class="space-y-5">
                    @csrf

                    {{-- Zeitplan --}}
                    <div>
                        <x-input-label for="schedule_time" value="Tägliche Backup-Zeit *" />
                        <x-text-input id="schedule_time" name="schedule_time" type="time"
                                      class="mt-1 block w-40"
                                      value="{{ old('schedule_time', $settings->schedule_time) }}" required />
                        <p class="mt-1 text-xs text-gray-400">Backup wird täglich zu dieser Uhrzeit automatisch erstellt.</p>
                        <x-input-error :messages="$errors->get('schedule_time')" class="mt-1" />
                    </div>

                    {{-- Aufbewahrung --}}
                    <div>
                        <x-input-label for="retention_count" value="Aufbewahrung (Anzahl Backups) *" />
                        <div class="mt-1 flex items-center gap-3">
                            <x-text-input id="retention_count" name="retention_count" type="number"
                                          min="1" max="365" class="block w-32"
                                          value="{{ old('retention_count', $settings->retention_count) }}" required />
                            <span class="text-sm text-gray-500">älteste werden automatisch gelöscht</span>
                        </div>
                        <x-input-error :messages="$errors->get('retention_count')" class="mt-1" />
                    </div>

                    {{-- Inhalt --}}
                    <div class="space-y-2">
                        <x-input-label value="Backup-Inhalt" />
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="backup_db" name="backup_db" value="1"
                                   @checked(old('backup_db', $settings->backup_db))
                                   class="rounded border-gray-300 text-indigo-600">
                            <label for="backup_db" class="text-sm text-gray-700">Datenbank (SQL-Dump, gzip-komprimiert)</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="backup_files" name="backup_files" value="1"
                                   @checked(old('backup_files', $settings->backup_files))
                                   class="rounded border-gray-300 text-indigo-600">
                            <label for="backup_files" class="text-sm text-gray-700">Dateien (storage/app/public, tar.gz)</label>
                        </div>
                        <div x-data="{ exportsEnabled: {{ $settings->backup_exports ? 'true' : 'false' }} }" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="backup_exports" name="backup_exports" value="1"
                                       x-model="exportsEnabled"
                                       @checked(old('backup_exports', $settings->backup_exports))
                                       class="rounded border-gray-300 text-indigo-600">
                                <label for="backup_exports" class="text-sm text-gray-700">Exporte (Modul-Daten als XLSX/PDF)</label>
                            </div>
                            <div x-show="exportsEnabled" class="ml-6 pl-3 border-l-2 border-gray-200 space-y-1">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="backup_exports_all" name="backup_exports_all" value="1"
                                           @checked(old('backup_exports_all', $settings->backup_exports_all))
                                           class="rounded border-gray-300 text-indigo-600">
                                    <label for="backup_exports_all" class="text-sm text-gray-700 font-medium">
                                        Alle Module exportieren
                                    </label>
                                </div>
                                <p class="text-xs text-gray-400 ml-5">
                                    Generiert beim Backup frische Exporte aus: Schulen, Stellenplan, Applikationen, Aufgaben, Haushalt.<br>
                                    Ohne diese Option werden nur bereits vorhandene Schulen-Exporte archiviert.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex justify-end">
                        <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- SMB-Export ──────────────────────────────────────────────────── --}}
            <div x-data="{ smbOpen: {{ $settings->smb_enabled ? 'true' : 'false' }} }"
                 class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">SMB-Export (Netzlaufwerk)</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Backup nach Abschluss automatisch auf ein SMB/Windows-Share übertragen</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $settings->smb_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $settings->smb_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('backup.settings.update') }}" class="space-y-4">
                    @csrf
                    {{-- Versteckte Felder aus dem Hauptformular mitschicken (werden durch separates form nicht überschrieben) --}}
                    {{-- SMB kann eigenständig gespeichert werden --}}

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="smb_enabled" name="smb_enabled" value="1"
                               x-model="smbOpen"
                               @checked(old('smb_enabled', $settings->smb_enabled))
                               class="rounded border-gray-300 text-indigo-600">
                        <label for="smb_enabled" class="text-sm font-medium text-gray-700">SMB-Export aktivieren</label>
                    </div>

                    <div x-show="smbOpen" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <x-input-label for="smb_server" value="Server / IP *" />
                            <x-text-input id="smb_server" name="smb_server" type="text"
                                          class="mt-1 block w-full"
                                          value="{{ old('smb_server', $settings->smb_server) }}"
                                          placeholder="192.168.1.10 oder fileserver.local" />
                            <x-input-error :messages="$errors->get('smb_server')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="smb_share" value="Freigabe-Name *" />
                            <x-text-input id="smb_share" name="smb_share" type="text"
                                          class="mt-1 block w-full"
                                          value="{{ old('smb_share', $settings->smb_share) }}"
                                          placeholder="Backups" />
                            <x-input-error :messages="$errors->get('smb_share')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="smb_domain" value="Domäne (optional)" />
                            <x-text-input id="smb_domain" name="smb_domain" type="text"
                                          class="mt-1 block w-full"
                                          value="{{ old('smb_domain', $settings->smb_domain) }}"
                                          placeholder="DOMAIN" />
                        </div>
                        <div>
                            <x-input-label for="smb_username" value="Benutzername" />
                            <x-text-input id="smb_username" name="smb_username" type="text"
                                          class="mt-1 block w-full"
                                          value="{{ old('smb_username', $settings->smb_username) }}"
                                          placeholder="backup-user" />
                        </div>
                        <div>
                            <x-input-label for="smb_password" value="Passwort" />
                            <x-text-input id="smb_password" name="smb_password" type="password"
                                          class="mt-1 block w-full"
                                          placeholder="{{ $settings->smb_password ? '(gespeichert – leer lassen zum Beibehalten)' : 'Passwort eingeben' }}" />
                        </div>
                        <div>
                            <x-input-label for="smb_path" value="Unterverzeichnis auf Share (optional)" />
                            <x-text-input id="smb_path" name="smb_path" type="text"
                                          class="mt-1 block w-full"
                                          value="{{ old('smb_path', $settings->smb_path) }}"
                                          placeholder="IT-Cockpit/Backups" />
                            <p class="text-xs text-gray-400 mt-1">
                                Pfad relativ zur Freigabe. Wird automatisch erstellt.
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-3">
                        <div x-show="smbOpen">
                            <form method="POST" action="{{ route('backup.settings.smb-test') }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                                    </svg>
                                    Verbindung testen
                                </button>
                            </form>
                        </div>
                        <x-primary-button type="submit">SMB-Einstellungen speichern</x-primary-button>
                    </div>
                </form>

                @if($settings->smb_enabled && $settings->smb_server)
                <div class="mt-3 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                    Ziel: <code class="font-mono">//{{ $settings->smb_server }}/{{ $settings->smb_share }}{{ $settings->smb_path ? '/' . $settings->smb_path : '' }}</code>
                    &nbsp;·&nbsp; Upload als <code class="font-mono">backup_YYYY-MM-DD_HHMMSS.tar.gz</code>
                </div>
                @endif
            </div>

            {{-- Speicherort --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600">
                <strong>Lokaler Speicherort:</strong>
                <code class="ml-1 text-xs bg-gray-100 px-1.5 py-0.5 rounded">storage/app/backups/</code>
                auf dem Server.
            </div>

        </div>
    </div>
</x-app-layout>
