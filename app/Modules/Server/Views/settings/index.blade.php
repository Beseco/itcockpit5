<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('server.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Server – Einstellungen</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- LDAP Sync --}}
            @can('server.sync')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">LDAP-Synchronisation</h3>
                        <form action="{{ route('server.sync') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600">
                                LDAP Sync jetzt ausführen
                            </button>
                        </form>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-1">
                            @if ($lastSync)
                                Letzter Sync: <strong>{{ \Carbon\Carbon::parse($lastSync)->format('d.m.Y H:i') }} Uhr</strong>
                            @else
                                Noch kein Sync durchgeführt.
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mb-4">Verwendet LDAP-Verbindung aus AD-Benutzer-Einstellungen.</p>

                        {{-- OU-Liste --}}
                        <div class="mb-4 space-y-1">
                            @forelse ($syncOus as $ou)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-gray-50 rounded">
                                    <div class="flex items-center gap-2 min-w-0">
                                        {{-- Enabled-Indikator --}}
                                        <span class="flex-shrink-0 w-2 h-2 rounded-full {{ $ou->enabled ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                        <div class="min-w-0">
                                            @if ($ou->label)
                                                <span class="text-sm font-medium text-gray-800">{{ $ou->label }}</span>
                                                <span class="text-xs text-gray-400 ml-1 font-mono">{{ $ou->distinguished_name }}</span>
                                            @else
                                                <span class="text-sm font-mono text-gray-800">{{ $ou->distinguished_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                        {{-- Toggle --}}
                                        <form action="{{ route('server.settings.sync-ous.toggle', $ou) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs {{ $ou->enabled ? 'text-gray-500 hover:text-gray-700' : 'text-green-600 hover:text-green-800' }}">
                                                {{ $ou->enabled ? 'Deaktivieren' : 'Aktivieren' }}
                                            </button>
                                        </form>
                                        {{-- Löschen --}}
                                        <form action="{{ route('server.settings.sync-ous.destroy', $ou) }}" method="POST"
                                              onsubmit="return confirm('OU wirklich löschen?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-amber-600">Keine OUs konfiguriert – Sync wird fehlschlagen.</p>
                            @endforelse
                        </div>

                        {{-- Neue OU hinzufügen --}}
                        @can('server.config')
                            <form action="{{ route('server.settings.sync-ous.store') }}" method="POST"
                                  class="flex gap-2 items-end mt-3">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Distinguished Name (DN)</label>
                                    <input type="text" name="distinguished_name"
                                           placeholder="OU=Server,DC=example,DC=lan" required
                                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono">
                                </div>
                                <div class="w-48">
                                    <label class="block text-xs text-gray-500 mb-1">Bezeichnung (optional)</label>
                                    <input type="text" name="label" placeholder="z.B. Produktiv-Server"
                                           class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                </div>
                                <button type="submit"
                                        class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 whitespace-nowrap">
                                    OU hinzufügen
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endcan

            {{-- CheckMK Integration --}}
            @can('server.config')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"
                 x-data="{
                    testing: false, testResult: null, testOk: null,
                    async runTest() {
                        this.testing = true; this.testResult = null;
                        const form = document.getElementById('checkmk-settings-form');
                        const data = new FormData(form);
                        data.append('_method', 'POST');
                        try {
                            const r = await fetch('{{ route('server.settings.checkmk.test') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: data,
                            });
                            const j = await r.json();
                            this.testOk = j.ok;
                            this.testResult = j.message;
                        } catch(e) { this.testOk = false; this.testResult = e.toString(); }
                        finally { this.testing = false; }
                    }
                 }">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">CheckMK Integration</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Ping, CPU, RAM, Festplatte, Uptime direkt in der Server-Detailansicht</p>
                    </div>
                    @if($checkMkSettings->isConfigured())
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Aktiv</span>
                    @else
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Inaktiv</span>
                    @endif
                </div>
                <div class="p-6 space-y-5">
                    <form id="checkmk-settings-form" method="POST" action="{{ route('server.settings.checkmk.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="cmk_enabled" name="enabled" value="1"
                                   @checked(old('enabled', $checkMkSettings->enabled))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="cmk_enabled" class="text-sm font-medium text-gray-700">CheckMK-Integration aktivieren</label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">CheckMK URL <span class="text-red-500">*</span></label>
                                <input type="url" name="url"
                                       value="{{ old('url', $checkMkSettings->url) }}"
                                       placeholder="https://checkmk.example.com"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <p class="text-xs text-gray-400 mt-0.5">Basis-URL des CheckMK-Servers (ohne Site-Pfad)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Site-Name <span class="text-red-500">*</span></label>
                                <input type="text" name="site"
                                       value="{{ old('site', $checkMkSettings->site) }}"
                                       placeholder="z.B. mysite"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Automation-User <span class="text-red-500">*</span></label>
                                <input type="text" name="username"
                                       value="{{ old('username', $checkMkSettings->username) }}"
                                       placeholder="automation"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Automation-Secret
                                    <span x-data x-tooltip="Wo finde ich das Secret?" class="ml-1 text-gray-400 cursor-help">?</span>
                                </label>
                                <input type="password" name="secret"
                                       placeholder="{{ $checkMkSettings->secret ? '••••••••••••• (leer lassen = nicht ändern)' : 'Secret eintragen…' }}"
                                       autocomplete="new-password"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>

                            <div class="sm:col-span-2 flex items-center gap-3">
                                <input type="checkbox" id="cmk_verify_ssl" name="verify_ssl" value="1"
                                       @checked(old('verify_ssl', $checkMkSettings->verify_ssl))
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="cmk_verify_ssl" class="text-sm text-gray-700">SSL-Zertifikat verifizieren</label>
                            </div>
                        </div>

                        {{-- Testergebnis --}}
                        <div x-show="testResult !== null"
                             :class="testOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                             class="border rounded-md px-4 py-3 text-sm flex items-start gap-2" x-cloak>
                            <span x-text="testOk ? '✓' : '✗'" class="font-bold shrink-0"></span>
                            <span x-text="testResult"></span>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <button type="button" @click="runTest()" :disabled="testing"
                                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-50 disabled:opacity-50">
                                <svg x-show="testing" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span x-text="testing ? 'Teste…' : 'Verbindung testen'"></span>
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                Einstellungen speichern
                            </button>
                        </div>
                    </form>

                    {{-- Hinweis: Automation Secret --}}
                    <div class="border border-blue-100 bg-blue-50 rounded-lg p-4 text-xs text-blue-800 space-y-1">
                        <p class="font-semibold text-blue-900">Wo finde ich das Automation-Secret?</p>
                        <ol class="list-decimal list-inside space-y-0.5 text-blue-700">
                            <li>In CheckMK: <strong>Setup → Users</strong> öffnen</li>
                            <li>Benutzer <strong>„automation"</strong> anklicken (oder einen eigenen Automation-User anlegen)</li>
                            <li>Im Benutzer-Formular nach unten scrollen: Abschnitt <strong>„Automation Secret"</strong></li>
                            <li>Das Secret kopieren oder über <strong>„Generate new secret"</strong> neu erzeugen</li>
                        </ol>
                        <p class="text-blue-600 mt-1">Der Automation-User benötigt mindestens die Rolle <strong>„Operator"</strong> oder Lesezugriff auf die gewünschten Hosts.</p>
                    </div>
                </div>
            </div>
            @endcan

            {{-- CheckMK Host-Test --}}
            @if($checkMkSettings->isConfigured())
            @can('server.config')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"
                 x-data="{
                    hostname: '',
                    loading: false,
                    result: null,
                    async lookup() {
                        if (!this.hostname.trim()) return;
                        this.loading = true; this.result = null;
                        const fd = new FormData();
                        fd.append('hostname', this.hostname.trim());
                        try {
                            const r = await fetch('{{ route('server.settings.checkmk.test-host') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: fd,
                            });
                            this.result = await r.json();
                        } catch(e) { this.result = { error: e.toString() }; }
                        finally { this.loading = false; }
                    }
                 }">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">CheckMK Host testen</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Prüfen ob und welche Sensoren CheckMK für einen bestimmten Host liefert</p>
                </div>
                <div class="p-6 space-y-4">

                    {{-- Eingabe --}}
                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Hostname in CheckMK</label>
                            <input type="text" x-model="hostname"
                                   @keydown.enter.prevent="lookup()"
                                   placeholder="z.B. srv-dc01 oder srv-dc01.lra.lan"
                                   class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        </div>
                        <button @click="lookup()" :disabled="loading || !hostname.trim()"
                                class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 whitespace-nowrap">
                            <span x-show="loading">
                                <svg class="inline animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Suche…
                            </span>
                            <span x-show="!loading">Host abfragen</span>
                        </button>
                    </div>

                    {{-- Fehler --}}
                    <div x-show="result && result.error" x-cloak
                         class="bg-red-50 border border-red-200 rounded-md px-4 py-3 text-sm text-red-700">
                        <strong>Fehler:</strong> <span x-text="result && result.error"></span>
                    </div>

                    {{-- Ergebnis --}}
                    <template x-if="result && !result.error">
                        <div class="space-y-3">

                            {{-- Host-Status --}}
                            <div class="flex items-center gap-3 py-2 border-b border-gray-100">
                                <span class="text-sm font-medium text-gray-600 w-36 shrink-0">Erreichbarkeit</span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-700': result.state === 0,
                                          'bg-red-100 text-red-700':    result.state === 1 || result.state === 2,
                                          'bg-gray-100 text-gray-500':  result.state == null
                                      }"
                                      x-text="result.state_label || '—'"></span>
                            </div>

                            {{-- Services --}}
                            <template x-if="result.services && result.services.length > 0">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
                                       x-text="'Gefundene Sensoren (' + result.services.length + ')'"></p>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Service</th>
                                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Status</th>
                                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="svc in result.services" :key="svc.name">
                                                    <tr class="border-t border-gray-100">
                                                        <td class="px-4 py-2.5 font-medium text-gray-800" x-text="svc.name"></td>
                                                        <td class="px-4 py-2.5">
                                                            <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                                                  :class="{
                                                                      'bg-green-100 text-green-700':   svc.state === 0,
                                                                      'bg-yellow-100 text-yellow-700': svc.state === 1,
                                                                      'bg-red-100 text-red-700':       svc.state === 2,
                                                                      'bg-gray-100 text-gray-500':     svc.state === 3 || svc.state == null
                                                                  }"
                                                                  x-text="svc.state_label"></span>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-gray-600 text-xs" x-text="svc.plugin_output || '—'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
            	            </template>

                            <template x-if="result.services && result.services.length === 0">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3 text-sm text-yellow-800">
                                    Host wurde gefunden, aber keine relevanten Services (CPU, RAM, Disk, Uptime) zurückgegeben.
                                    Prüfen Sie ob der Hostname in CheckMK korrekt eingetragen ist und die Services existieren.
                                </div>
                            </template>
                        </div>
                    </template>

                </div>
            </div>
            @endcan
            @endif

            {{-- vSphere Integration --}}
            @can('server.config')
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden"
                 x-data="{
                    testing: false, testResult: null, testOk: null,
                    async runTest() {
                        this.testing = true; this.testResult = null;
                        const form = document.getElementById('vsphere-settings-form');
                        const data = new FormData(form);
                        try {
                            const r = await fetch('{{ route('server.settings.vsphere.test') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: data,
                            });
                            const j = await r.json();
                            this.testOk = j.ok;
                            this.testResult = j.message;
                        } catch(e) { this.testOk = false; this.testResult = e.toString(); }
                        finally { this.testing = false; }
                    }
                 }">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">VMware vSphere Integration</h3>
                        <p class="text-xs text-gray-400 mt-0.5">VMs automatisch aus vCenter importieren – CPU, RAM, Speicher, Datastore, Status</p>
                    </div>
                    @if($vsphereSettings->isConfigured())
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Aktiv</span>
                    @else
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Inaktiv</span>
                    @endif
                </div>
                <div class="p-6 space-y-5">
                    <form id="vsphere-settings-form" method="POST" action="{{ route('server.settings.vsphere.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="vs_enabled" name="enabled" value="1"
                                   @checked(old('enabled', $vsphereSettings->enabled))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label for="vs_enabled" class="text-sm font-medium text-gray-700">vSphere-Integration aktivieren</label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">vCenter URL <span class="text-red-500">*</span></label>
                                <input type="text" name="vcenter_url"
                                       value="{{ old('vcenter_url', $vsphereSettings->vcenter_url) }}"
                                       placeholder="https://vcenter.lra.lan"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono">
                                <p class="text-xs text-gray-400 mt-0.5">Basis-URL des vCenter Servers (ohne /api)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Benutzername <span class="text-red-500">*</span></label>
                                <input type="text" name="username"
                                       value="{{ old('username', $vsphereSettings->username) }}"
                                       placeholder="administrator@vsphere.local"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Passwort</label>
                                <input type="password" name="password"
                                       placeholder="{{ $vsphereSettings->password ? '••••••••• (leer lassen = nicht ändern)' : 'Passwort eintragen…' }}"
                                       autocomplete="new-password"
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>

                            <div class="sm:col-span-2 flex items-center gap-3">
                                <input type="checkbox" id="vs_verify_ssl" name="verify_ssl" value="1"
                                       @checked(old('verify_ssl', $vsphereSettings->verify_ssl))
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="vs_verify_ssl" class="text-sm text-gray-700">SSL-Zertifikat verifizieren</label>
                                <span class="text-xs text-gray-400">(deaktivieren bei selbstsignierten Zertifikaten)</span>
                            </div>
                        </div>

                        <div x-show="testResult !== null"
                             :class="testOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                             class="border rounded-md px-4 py-3 text-sm flex items-start gap-2" x-cloak>
                            <span x-text="testOk ? '✓' : '✗'" class="font-bold shrink-0"></span>
                            <span x-text="testResult"></span>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <button type="button" @click="runTest()" :disabled="testing"
                                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-50 disabled:opacity-50">
                                <svg x-show="testing" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span x-text="testing ? 'Teste…' : 'Verbindung testen'"></span>
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                Einstellungen speichern
                            </button>
                        </div>
                    </form>

                    {{-- Hinweis --}}
                    <div class="border border-blue-100 bg-blue-50 rounded-lg p-4 text-xs text-blue-800 space-y-1">
                        <p class="font-semibold text-blue-900">Hinweise zur vSphere-Verbindung</p>
                        <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                            <li>Der Benutzer benötigt mindestens die vSphere-Rolle <strong>Read-Only</strong> auf dem vCenter-Objekt.</li>
                            <li>IP-Adressen und Hostnamen werden nur ausgelesen wenn <strong>VMware Tools</strong> in der VM installiert sind.</li>
                            <li>Beim Sync werden bestehende Server per Name oder Hostname gematcht. Neue VMs werden automatisch angelegt.</li>
                            <li>Manuell gepflegte Felder (Hostname, IP) werden beim Sync <strong>nicht überschrieben</strong>.</li>
                        </ul>
                    </div>
                </div>
            </div>
            @endcan

            {{-- vSphere Sync --}}
            @can('server.sync')
            @if($vsphereSettings->isConfigured())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">vSphere Synchronisation</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @php $lastVsphereSync = \App\Modules\Server\Models\Server::whereNotNull('vsphere_synced_at')->max('vsphere_synced_at'); @endphp
                            @if($lastVsphereSync)
                                Letzter Sync: <strong>{{ \Carbon\Carbon::parse($lastVsphereSync)->format('d.m.Y H:i') }} Uhr</strong>
                            @else
                                Noch kein Sync durchgeführt.
                            @endif
                        </p>
                    </div>
                    <form action="{{ route('server.vsphere-sync') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-violet-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-violet-700">
                            vSphere Sync jetzt ausführen
                        </button>
                    </form>
                </div>
            </div>
            @endif
            @endcan

            {{-- Erweiterbare Optionen --}}
            @foreach ($options as $category => $categoryOptions)
                @php
                    $catLabel = \App\Modules\Server\Models\ServerOption::CATEGORY_LABELS[$category] ?? $category;
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $catLabel }}</h3>
                    </div>
                    <div class="p-6">

                        {{-- Bestehende Optionen --}}
                        <div class="mb-4 space-y-1">
                            @forelse ($categoryOptions as $opt)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-800">{{ $opt->label }}</span>
                                    <form action="{{ route('server.settings.options.destroy', $opt) }}" method="POST"
                                          onsubmit="return confirm('Option \"{{ $opt->label }}\" wirklich löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Noch keine Optionen vorhanden.</p>
                            @endforelse
                        </div>

                        {{-- Neue Option hinzufügen --}}
                        <form action="{{ route('server.settings.options.store') }}" method="POST"
                              class="flex gap-2 items-end mt-3">
                            @csrf
                            <input type="hidden" name="category" value="{{ $category }}">
                            <div class="flex-1">
                                <input type="text" name="label" placeholder="Neue Option…" required
                                       class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            </div>
                            <button type="submit"
                                    class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 whitespace-nowrap">
                                Hinzufügen
                            </button>
                        </form>

                    </div>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
