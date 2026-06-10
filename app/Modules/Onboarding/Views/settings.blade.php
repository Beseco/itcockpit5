<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">AD-Benutzer – Einstellungen</h2>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Settings sub-tabs --}}
            <div class="flex gap-2 border-b border-gray-200 pb-0">
                @can('module.adusers.config')
                <a href="{{ route('adusers.settings') }}"
                   class="px-4 py-2 text-sm font-medium rounded-t border-b-2 border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300 -mb-px">
                    AD-Verbindung &amp; Sync
                </a>
                @endcan
                <a href="{{ route('onboarding.settings') }}"
                   class="px-4 py-2 text-sm font-medium rounded-t border-b-2 border-indigo-600 text-indigo-700 bg-white -mb-px">
                    Onboarding
                </a>
            </div>

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('onboarding.settings.update') }}" class="space-y-6">
                @csrf @method('PUT')

                {{-- LDAP Write-Account --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">LDAP Write-Account</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Account mit Schreibrechten auf die Benutzer-OUs. Leer lassen um den Sync-Account (aus AD-Benutzer Einstellungen) zu verwenden.
                        <strong>Wichtig: Zum Setzen von AD-Passwörtern ist LDAPS (Port 636) erforderlich.</strong>
                    </p>

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="ldap_write_bind_dn" value="Bind-DN (Write-Account)" />
                            <x-text-input id="ldap_write_bind_dn" name="ldap_write_bind_dn" type="text" class="mt-1 block w-full font-mono text-xs"
                                          value="{{ old('ldap_write_bind_dn', $settings->ldap_write_bind_dn) }}"
                                          placeholder="CN=it-provisioner,OU=Dienste,DC=lra,DC=lan" autocomplete="off" />
                            <x-input-error :messages="$errors->get('ldap_write_bind_dn')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="ldap_write_bind_password" value="Passwort" />
                            <x-text-input id="ldap_write_bind_password" name="ldap_write_bind_password" type="password" class="mt-1 block w-full"
                                          placeholder="{{ $settings->ldap_write_bind_dn ? '(gespeichert – leer lassen zum Beibehalten)' : 'Passwort eingeben' }}"
                                          autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('ldap_write_bind_password')" class="mt-1" />
                        </div>

                        {{-- Verbindungstest --}}
                        <div x-data="{
                                testing: false, result: null, ok: null,
                                async test() {
                                    this.testing = true; this.result = null;
                                    try {
                                        const r = await fetch('{{ route('onboarding.settings.test-ldap') }}', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                        });
                                        const j = await r.json();
                                        this.ok = j.ok; this.result = j.message;
                                    } catch(e) { this.ok = false; this.result = e.toString(); }
                                    finally { this.testing = false; }
                                }
                             }">
                            <div class="flex items-center gap-3 flex-wrap">
                                <button type="button" @click="test()" :disabled="testing"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    <span x-text="testing ? 'Teste …' : 'Verbindung testen'">Verbindung testen</span>
                                </button>
                                <span x-show="result !== null" :class="ok ? 'text-green-700' : 'text-red-700'"
                                      class="text-xs" x-text="result" x-cloak></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Temporäres Admin-Passwort --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Temporäres Passwort (für den Admin)</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Dieses Passwort wird beim Anlegen neuer Benutzer als temporäres Passwort (Phase 1) gesetzt.
                        Der Admin kann sich damit als neuer Benutzer anmelden um die Einrichtung durchzuführen.
                        Das endgültige Passwort (mit Änderungspflicht) wird erst beim Abschließen der Todo-Liste vergeben.
                        <br>Leer lassen = zufälliges Passwort wird generiert.
                    </p>
                    <div x-data="{ visible: false }">
                        <x-input-label for="temp_password" value="Festes temporäres Passwort" />
                        <div class="mt-1 flex gap-2 items-center">
                            <x-text-input id="temp_password" name="temp_password"
                                          :type="'password'"
                                          x-bind:type="visible ? 'text' : 'password'"
                                          class="block flex-1 font-mono"
                                          placeholder="{{ $settings->temp_password ? '(gespeichert – leer lassen zum Beibehalten)' : 'z.B. Willkommen1!' }}"
                                          autocomplete="new-password" />
                            <button type="button" @click="visible = !visible"
                                    class="shrink-0 px-3 py-2 text-xs border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50"
                                    x-text="visible ? 'Ausblenden' : 'Anzeigen'">Anzeigen</button>
                        </div>
                        @if($settings->temp_password)
                            <p class="mt-1 text-xs text-green-700">✓ Festes Passwort konfiguriert – wird für alle neuen Benutzer verwendet.</p>
                        @else
                            <p class="mt-1 text-xs text-gray-400">Noch kein festes Passwort gesetzt – es wird ein zufälliges generiert.</p>
                        @endif
                        <x-input-error :messages="$errors->get('temp_password')" class="mt-1" />
                    </div>
                </div>

                {{-- Gruppen-Suchbasis --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Gruppen-Suchbasis</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        OU aus der Sicherheits- und Verteilergruppen geladen werden (z.B. <code class="bg-gray-100 px-1 rounded">OU=Gruppen,OU=LRA-FS,DC=lra,DC=lan</code>).
                        Leer lassen = gesamtes Verzeichnis (Base DN aus AD-Einstellungen).
                    </p>
                    <div>
                        <x-input-label for="group_search_base_dn" value="Gruppen-OU (Base DN)" />
                        <x-text-input id="group_search_base_dn" name="group_search_base_dn" type="text"
                                      class="mt-1 block w-full font-mono text-xs"
                                      value="{{ old('group_search_base_dn', $settings->group_search_base_dn) }}"
                                      placeholder="OU=Gruppen,OU=LRA-FS,DC=lra,DC=lan" />
                        <x-input-error :messages="$errors->get('group_search_base_dn')" class="mt-1" />
                    </div>

                    <div class="mt-4"
                         x-data="{
                             testing: false, result: null, ok: null, security: null, distribution: null,
                             async test() {
                                 this.testing = true; this.result = null;
                                 try {
                                     const r = await fetch('{{ route('onboarding.settings.test-groups') }}', {
                                         method: 'POST',
                                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                     });
                                     const j = await r.json();
                                     this.ok = j.ok; this.result = j.message;
                                     this.security = j.security ?? null;
                                     this.distribution = j.distribution ?? null;
                                 } catch(e) { this.ok = false; this.result = e.toString(); }
                                 finally { this.testing = false; }
                             }
                         }">
                        <div class="flex items-center gap-3 flex-wrap">
                            <button type="button" @click="test()" :disabled="testing"
                                    class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                <span x-text="testing ? 'Suche …' : 'Gruppen zählen'">Gruppen zählen</span>
                            </button>
                            <span x-show="result !== null" x-cloak
                                  :class="ok ? 'text-green-700' : 'text-red-700'"
                                  class="text-xs" x-text="result"></span>
                        </div>
                        <div x-show="ok && security !== null" x-cloak class="mt-3 flex gap-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full font-medium">
                                <span x-text="security"></span> Sicherheitsgruppen
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">
                                <span x-text="distribution"></span> Verteilergruppen
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Exchange-Postfach --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Exchange – Postfach automatisch anlegen</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Nach dem Anlegen eines AD-Benutzers wird automatisch per
                        <code class="bg-gray-100 px-1 rounded">pwsh</code> (PowerShell Core) ein Exchange-Postfach aktiviert.
                        Erfordert <strong>pwsh auf dem Server</strong>
                        (<a href="https://aka.ms/install-powershell" target="_blank" class="underline text-indigo-600">Installationsanleitung</a>)
                        und WinRM-Zugriff auf den Exchange-Server.
                        Alle Felder leer lassen um das Feature zu deaktivieren.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="exchange_url" value="PowerShell-Endpoint URL" />
                            <x-text-input id="exchange_url" name="exchange_url" type="text" class="mt-1 block w-full font-mono text-xs"
                                          value="{{ old('exchange_url', $settings->exchange_url) }}"
                                          placeholder="http://mail.lra.lan/PowerShell/" />
                            <x-input-error :messages="$errors->get('exchange_url')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="exchange_user" value="Benutzername (UPN oder DOMAIN\user)" />
                                <x-text-input id="exchange_user" name="exchange_user" type="text" class="mt-1 block w-full font-mono text-xs"
                                              value="{{ old('exchange_user', $settings->exchange_user) }}"
                                              placeholder="admin@lra.lan oder LRA\admin" autocomplete="off" />
                                <x-input-error :messages="$errors->get('exchange_user')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="exchange_auth" value="Authentifizierungsmethode" />
                                <select id="exchange_auth" name="exchange_auth"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                    @foreach(['Negotiate' => 'Negotiate (empfohlen)', 'Basic' => 'Basic', 'Kerberos' => 'Kerberos', 'NTLM' => 'NTLM'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('exchange_auth', $settings->exchange_auth ?? 'Negotiate') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="exchange_password" value="Passwort" />
                                <x-text-input id="exchange_password" name="exchange_password" type="password" class="mt-1 block w-full"
                                              placeholder="{{ $settings->exchange_user ? '(gespeichert – leer lassen zum Beibehalten)' : 'Passwort eingeben' }}"
                                              autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('exchange_password')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="exchange_mailbox_db" value="Postfach-Datenbank (optional)" />
                                <x-text-input id="exchange_mailbox_db" name="exchange_mailbox_db" type="text" class="mt-1 block w-full font-mono text-xs"
                                              value="{{ old('exchange_mailbox_db', $settings->exchange_mailbox_db) }}"
                                              placeholder="Mailbox-DB01" />
                                <p class="mt-1 text-xs text-gray-400">Leer = Exchange wählt Standard-Datenbank.</p>
                            </div>
                        </div>

                        {{-- Exchange-Verbindungstest --}}
                        <div x-data="{
                                testing: false, result: null, ok: null,
                                async test() {
                                    this.testing = true; this.result = null;
                                    try {
                                        const r = await fetch('{{ route('onboarding.settings.test-exchange') }}', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                        });
                                        const j = await r.json();
                                        this.ok = j.ok; this.result = j.message;
                                    } catch(e) { this.ok = false; this.result = e.toString(); }
                                    finally { this.testing = false; }
                                }
                             }">
                            <div class="flex items-center gap-3 flex-wrap">
                                <button type="button" @click="test()" :disabled="testing"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    <span x-text="testing ? 'Teste …' : 'Verbindung testen'">Verbindung testen</span>
                                </button>
                                <span x-show="result !== null" :class="ok ? 'text-green-700' : 'text-red-700'"
                                      class="text-xs" x-text="result" x-cloak></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Heimatverzeichnis / SMB --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Heimatverzeichnis – Ordner automatisch anlegen</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Wenn in der Vorlage ein Heimatverzeichnis-Muster hinterlegt ist, wird der Ordner auf dem Fileserver
                        automatisch angelegt und dem Benutzer wird <strong>Full Control</strong> darauf gewährt.<br>
                        Zugangsdaten: ein Domain-Konto mit Schreibrecht auf den Basisordner des Shares
                        (Format: <code class="bg-gray-100 px-1">DOMAIN\Benutzername</code>).
                        Alle Felder leer lassen um das Feature zu deaktivieren.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="smb_user" value="SMB-Benutzer (DOMAIN\user)" />
                            <x-text-input id="smb_user" name="smb_user" type="text" class="mt-1 block w-full font-mono text-xs"
                                          value="{{ old('smb_user', $settings->smb_user) }}"
                                          placeholder="LRA\svc-itcockpit" autocomplete="off" />
                            <x-input-error :messages="$errors->get('smb_user')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="smb_password" value="SMB-Passwort" />
                            <x-text-input id="smb_password" name="smb_password" type="password" class="mt-1 block w-full"
                                          placeholder="{{ $settings->smb_user ? '(gespeichert – leer lassen zum Beibehalten)' : 'Passwort eingeben' }}"
                                          autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('smb_password')" class="mt-1" />
                        </div>
                    </div>

                    {{-- SMB-Verbindungstest --}}
                    <div class="mt-4"
                         x-data="{
                            testing: false, result: null, ok: null,
                            async test() {
                                this.testing = true; this.result = null;
                                try {
                                    const r = await fetch('{{ route('onboarding.settings.test-smb') }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                    });
                                    const j = await r.json();
                                    this.ok = j.ok; this.result = j.message;
                                } catch(e) { this.ok = false; this.result = e.toString(); }
                                finally { this.testing = false; }
                            }
                         }">
                        <div class="flex items-center gap-3 flex-wrap">
                            <button type="button" @click="test()" :disabled="testing"
                                    class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                <span x-text="testing ? 'Teste …' : 'Verbindung testen'">Verbindung testen</span>
                            </button>
                            <span class="text-xs text-gray-400">Testet ob smbclient den Share erreicht. Bitte zuerst Einstellungen speichern.</span>
                            <span x-show="result !== null" :class="ok ? 'text-green-700' : 'text-red-700'"
                                  class="text-xs" x-text="result" x-cloak></span>
                        </div>
                    </div>
                </div>

                {{-- E-Mail-Vorlagen --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">E-Mail-Vorlagen (global)</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Variablen: <code class="bg-gray-100 px-1 rounded">%vorname%</code>, <code class="bg-gray-100 px-1 rounded">%nachname%</code>,
                        <code class="bg-gray-100 px-1 rounded">%benutzername%</code>, <code class="bg-gray-100 px-1 rounded">%upn%</code>,
                        <code class="bg-gray-100 px-1 rounded">%rufnummer%</code>, <code class="bg-gray-100 px-1 rounded">%passwort%</code>
                    </p>
                    <div class="space-y-5">
                        <div>
                            <x-input-label for="welcome_mail_subject" value="Betreff Begrüßungsmail *" />
                            <x-text-input id="welcome_mail_subject" name="welcome_mail_subject" type="text" class="mt-1 block w-full"
                                          value="{{ old('welcome_mail_subject', $settings->welcome_mail_subject) }}" required />
                            <x-input-error :messages="$errors->get('welcome_mail_subject')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="welcome_mail_body" value="Text Begrüßungsmail" />
                            <textarea id="welcome_mail_body" name="welcome_mail_body" rows="8"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono"
                                      >{{ old('welcome_mail_body', $settings->welcome_mail_body) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="supervisor_mail_subject" value="Betreff Vorgesetzten-Mail *" />
                            <x-text-input id="supervisor_mail_subject" name="supervisor_mail_subject" type="text" class="mt-1 block w-full"
                                          value="{{ old('supervisor_mail_subject', $settings->supervisor_mail_subject) }}" required />
                            <x-input-error :messages="$errors->get('supervisor_mail_subject')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="supervisor_mail_body" value="Text Vorgesetzten-Mail" />
                            <textarea id="supervisor_mail_body" name="supervisor_mail_body" rows="6"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm font-mono"
                                      >{{ old('supervisor_mail_body', $settings->supervisor_mail_body) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
