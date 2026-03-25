<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">AD-Benutzer – Einstellungen</h2>
            <a href="{{ route('adusers.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">← Zur Benutzerliste</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- LDAP-Konfiguration --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">LDAP-Verbindung</h3>

                <form method="POST" action="{{ route('adusers.settings.update') }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <x-input-label for="server" value="Server (Host/IP) *" />
                            <x-text-input id="server" name="server" type="text" class="mt-1 block w-full"
                                          value="{{ old('server', $settings->server) }}"
                                          placeholder="ldap.firma.de oder 10.0.0.10" required />
                            <x-input-error :messages="$errors->get('server')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="port" value="Port *" />
                            <x-text-input id="port" name="port" type="number" class="mt-1 block w-full"
                                          value="{{ old('port', $settings->port ?? 389) }}" required />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="base_dn" value="Base DN *" />
                        <x-text-input id="base_dn" name="base_dn" type="text" class="mt-1 block w-full"
                                      value="{{ old('base_dn', $settings->base_dn) }}"
                                      placeholder="DC=firma,DC=de" required />
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="anonymous_bind" name="anonymous_bind" value="1"
                               @checked(old('anonymous_bind', $settings->anonymous_bind))
                               class="rounded border-gray-300 text-indigo-600">
                        <x-input-label for="anonymous_bind" value="Anonymous Bind (ohne Anmeldedaten)" class="!mb-0" />
                    </div>

                    <div id="bind-fields" class="{{ old('anonymous_bind', $settings->anonymous_bind) ? 'hidden' : '' }}">
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="bind_dn" value="Bind-DN (Benutzerkonto)" />
                                <x-text-input id="bind_dn" name="bind_dn" type="text" class="mt-1 block w-full"
                                              value="{{ old('bind_dn', $settings->bind_dn) }}"
                                              placeholder="CN=ldap-reader,OU=Service,DC=firma,DC=de" />
                            </div>
                            <div x-data="{ show: false }">
                                <x-input-label for="bind_password" value="Passwort" />
                                <div class="mt-1 flex gap-2">
                                    <input id="bind_password" name="bind_password"
                                           :type="show ? 'text' : 'password'"
                                           class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                           placeholder="{{ $settings->bind_password ? '••••••••••••' : 'Passwort eingeben' }}"
                                           autocomplete="new-password">
                                    <button type="button" @click="show = !show"
                                            class="px-3 py-1.5 text-xs text-gray-500 border border-gray-300 rounded-md hover:bg-gray-50">
                                        <span x-text="show ? 'Verbergen' : 'Anzeigen'">Anzeigen</span>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Leer lassen um das gespeicherte Passwort beizubehalten.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="use_ssl" name="use_ssl" value="1"
                               @checked(old('use_ssl', $settings->use_ssl))
                               class="rounded border-gray-300 text-indigo-600">
                        <x-input-label for="use_ssl" value="SSL/LDAPS verwenden (Port 636)" class="!mb-0" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                        <div>
                            <x-input-label for="sync_interval_hours" value="Sync-Intervall (Stunden) *" />
                            <x-text-input id="sync_interval_hours" name="sync_interval_hours" type="number"
                                          min="1" max="168" class="mt-1 block w-full"
                                          value="{{ old('sync_interval_hours', $settings->sync_interval_hours ?? 24) }}" required />
                        </div>
                        <div>
                            <x-input-label for="max_inactive_days" value="Max. Inaktivität (Tage) *" />
                            <x-text-input id="max_inactive_days" name="max_inactive_days" type="number"
                                          min="1" class="mt-1 block w-full"
                                          value="{{ old('max_inactive_days', $settings->max_inactive_days ?? 90) }}" required />
                            <p class="mt-1 text-xs text-gray-400">Benutzer die länger nicht synchronisiert wurden, können gelöscht werden.</p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Test-Bereich --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="{
                     connResult: null,
                     queryResult: null,
                     loading: false,
                     async doFetch(url) {
                         const r = await fetch(url, {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                 'Accept': 'application/json',
                             }
                         });
                         const text = await r.text();
                         try {
                             return JSON.parse(text);
                         } catch(e) {
                             return { success: false, message: 'HTTP ' + r.status + ' – Server antwortete kein JSON. Bitte Migrations auf Produktion ausführen: keyhelp-php83 artisan migrate' };
                         }
                     },
                     async testConnection() {
                         this.loading = true; this.connResult = null;
                         try { this.connResult = await this.doFetch('{{ route('adusers.settings.test-connection') }}'); }
                         catch(e) { this.connResult = { success: false, message: e.message }; }
                         this.loading = false;
                     },
                     async testQuery() {
                         this.loading = true; this.queryResult = null;
                         try { this.queryResult = await this.doFetch('{{ route('adusers.settings.test-query') }}'); }
                         catch(e) { this.queryResult = { success: false, message: e.message }; }
                         this.loading = false;
                     }
                 }">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Verbindungstest</h3>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="testConnection()" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 border border-indigo-300 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100 transition disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                        Verbindung testen
                    </button>
                    <button type="button" @click="testQuery()" :disabled="loading"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-100 transition disabled:opacity-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                        </svg>
                        Testabfrage (Anzahl User)
                    </button>
                </div>

                <div x-show="connResult" x-cloak class="mt-3">
                    <div :class="connResult?.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                         class="p-3 border rounded-md text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             :style="connResult?.success ? 'color:#16a34a' : 'color:#dc2626'">
                            <path x-show="connResult?.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <path x-show="!connResult?.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span x-text="connResult?.message"></span>
                    </div>
                </div>

                <div x-show="queryResult" x-cloak class="mt-3">
                    <div :class="queryResult?.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                         class="p-3 border rounded-md text-sm">
                        <span x-text="queryResult?.message"></span>
                        <span x-show="queryResult?.success && queryResult?.count !== undefined"
                              class="ml-2 font-bold" x-text="'(' + queryResult?.count + ' Benutzer gefunden)'"></span>
                    </div>
                </div>
            </div>

            {{-- Manuelle Synchronisation --}}
            @can('adusers.sync')
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Manuelle Synchronisation</h3>
                <p class="text-sm text-gray-500 mb-3">Startet sofort einen vollständigen Import aller AD-Benutzer.</p>
                <form method="POST" action="{{ route('adusers.sync') }}">
                    @csrf
                    <x-primary-button type="submit">Jetzt synchronisieren</x-primary-button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
document.getElementById('anonymous_bind').addEventListener('change', function() {
    document.getElementById('bind-fields').classList.toggle('hidden', this.checked);
});
</script>
@endpush
