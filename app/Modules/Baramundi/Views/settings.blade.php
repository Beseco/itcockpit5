<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('baramundi.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Baramundi – Einstellungen</h2>
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
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Allgemeine Einstellungen --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-5">Scan-Konfiguration</h3>

                <form method="POST" action="{{ route('baramundi.settings.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="scan_interval_minutes" value="Scan-Intervall (Minuten) *" />
                        <div class="mt-1 flex items-center gap-3">
                            <x-text-input id="scan_interval_minutes" name="scan_interval_minutes" type="number"
                                          min="1" max="1440" class="block w-28"
                                          value="{{ old('scan_interval_minutes', $settings->scan_interval_minutes) }}" required />
                            <span class="text-sm text-gray-500">Minuten (1–1440)</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Standardwert: 15 Minuten. Änderungen werden nach einem Neustart des Webservers wirksam.
                        </p>
                        <x-input-error :messages="$errors->get('scan_interval_minutes')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="notification_email" value="Benachrichtigungs-E-Mail-Adresse" />
                        <x-text-input id="notification_email" name="notification_email" type="email"
                                      class="mt-1 block w-full"
                                      value="{{ old('notification_email', $settings->notification_email) }}"
                                      placeholder="it-admin@example.de" />
                        <p class="mt-1 text-xs text-gray-400">
                            An diese Adresse werden Benachrichtigungen für alle Pakete (sofern aktiviert) versendet.
                        </p>
                        <x-input-error :messages="$errors->get('notification_email')" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="email_on_smb_error" name="email_on_smb_error" value="1"
                               @checked(old('email_on_smb_error', $settings->email_on_smb_error))
                               class="rounded border-gray-300 text-indigo-600">
                        <label for="email_on_smb_error" class="text-sm text-gray-700">
                            E-Mail auch bei SMB-Erreichbarkeitsproblemen senden
                        </label>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex justify-end">
                        <x-primary-button type="submit">Einstellungen speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- SMB-Zugangsdaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">SMB-Zugangsdaten (optional)</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Nur erforderlich wenn der Apache-Prozess keinen automatischen Domänenzugriff auf die Baramundi-Freigaben hat.
                            Die Verbindung wird vor jedem Scan über <code class="bg-gray-100 px-1 rounded">net use</code> hergestellt und danach wieder getrennt.
                        </p>
                    </div>
                    @if($settings->smb_username)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Konfiguriert</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Nicht konfiguriert</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('baramundi.settings.credentials.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="smb_domain" value="Domäne (optional)" />
                            <x-text-input id="smb_domain" name="smb_domain" type="text" class="mt-1 block w-full"
                                          value="{{ old('smb_domain', $settings->smb_domain) }}"
                                          placeholder="LRA" />
                            <p class="mt-1 text-xs text-gray-400">z.B. LRA oder lra.lan</p>
                        </div>
                        <div>
                            <x-input-label for="smb_username" value="Benutzername" />
                            <x-text-input id="smb_username" name="smb_username" type="text" class="mt-1 block w-full"
                                          value="{{ old('smb_username', $settings->smb_username) }}"
                                          placeholder="bara-reader" autocomplete="off" />
                        </div>
                        <div>
                            <x-input-label for="smb_password" value="Passwort" />
                            <x-text-input id="smb_password" name="smb_password" type="password" class="mt-1 block w-full"
                                          placeholder="{{ $settings->smb_password ? '(gespeichert – leer lassen zum Beibehalten)' : 'Passwort eingeben' }}"
                                          autocomplete="new-password" />
                        </div>
                    </div>

                    @if($settings->smb_username)
                        <p class="text-xs text-gray-500">
                            Verbindung wird hergestellt als:
                            <code class="bg-gray-100 px-1 rounded">{{ $settings->smb_domain ? $settings->smb_domain . '\\' . $settings->smb_username : $settings->smb_username }}</code>
                        </p>
                    @endif

                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-3">
                        @if($settings->smb_username)
                            <form method="POST" action="{{ route('baramundi.settings.credentials.update') }}" class="inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="smb_clear" value="1">
                                <button type="submit" onclick="return confirm('SMB-Zugangsdaten wirklich löschen?')"
                                        class="text-xs text-red-500 hover:text-red-700">
                                    Zugangsdaten entfernen
                                </button>
                            </form>
                        @else
                            <span></span>
                        @endif
                        <x-primary-button type="submit">Zugangsdaten speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- UNC-Pfad testen --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="{
                     testing: false, testResult: null, testOk: null,
                     uncPath: '',
                     async runTest() {
                         if (!this.uncPath) return;
                         this.testing = true; this.testResult = null;
                         try {
                             const r = await fetch('{{ route('baramundi.settings.test-smb') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                     'Accept':       'application/json',
                                     'Content-Type': 'application/json',
                                 },
                                 body: JSON.stringify({ unc_path: this.uncPath }),
                             });
                             const j = await r.json();
                             this.testOk     = j.ok;
                             this.testResult = j.message;
                         } catch(e) { this.testOk = false; this.testResult = e.toString(); }
                         finally { this.testing = false; }
                     }
                 }">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">UNC-Pfad testen</h3>
                <p class="text-xs text-gray-400 mb-4">
                    Prüft ob ein UNC-Pfad vom Webserver aus erreichbar ist. Der Apache-Prozess muss die nötigen
                    Windows-Zugriffsrechte auf die Freigabe besitzen.
                </p>

                <div class="flex gap-3 items-start">
                    <div class="flex-1">
                        <x-text-input type="text" x-model="uncPath" class="block w-full font-mono text-sm"
                                      placeholder="\\Bara-01\dip$\ManagedSoftware\source\TeamViewer\TeamViewerHost\15.x-x64" />
                    </div>
                    <button @click="runTest()" :disabled="testing || !uncPath"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50 disabled:opacity-50 whitespace-nowrap">
                        <span x-text="testing ? 'Teste …' : 'Verbindung testen'">Verbindung testen</span>
                    </button>
                </div>

                <div x-show="testResult !== null"
                     :class="testOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                     class="mt-3 border rounded-md px-4 py-3 text-sm flex items-start gap-2" x-cloak>
                    <span x-text="testOk ? '✓' : '✗'" class="font-bold shrink-0"></span>
                    <span x-text="testResult"></span>
                </div>
            </div>

            {{-- Hinweise --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                <strong>Hinweis zu Zugriffsrechten:</strong><br>
                PHP greift auf UNC-Pfade mit dem Dienstkonto des Apache-Prozesses zu.
                Stellen Sie sicher, dass dieses Windows-Konto Lesezugriff auf die Baramundi-Freigaben besitzt.
                Nutzen Sie den obigen Test-Button um die Erreichbarkeit zu prüfen.
            </div>

        </div>
    </div>
</x-app-layout>
