<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Onboarding – Einstellungen</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
