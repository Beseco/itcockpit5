<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800">Ergebnis</h2>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status --}}
            @if($record->status === 'erfolgreich')
                @if($record->error_message)
                    {{-- Erfolgreich angelegt, aber Passwort-Warnung vorhanden --}}
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-5">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-500 text-xl">⚠</span>
                            <div>
                                <p class="font-semibold text-amber-800">
                                    Benutzer {{ $record->vorname }} {{ $record->nachname }} wurde im AD angelegt – Konto ist noch deaktiviert.
                                </p>
                                <p class="text-sm text-amber-700 mt-2">
                                    <strong>Passwort konnte nicht gesetzt werden:</strong> {{ $record->error_message }}
                                </p>
                                <div class="mt-3 p-3 bg-amber-100 rounded text-sm text-amber-800">
                                    <strong>Nächste Schritte:</strong>
                                    <ol class="mt-1 ml-4 list-decimal space-y-1">
                                        <li>Passwort in ADUC (Active Directory-Benutzer und -Computer) manuell setzen</li>
                                        <li>Konto aktivieren</li>
                                        <li>Oder: LDAPS in den <a href="{{ route('adusers.settings') }}" class="underline">AD-Benutzer Einstellungen</a> aktivieren (Port 636, SSL), dann funktioniert es automatisch</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-5">
                        <div class="flex items-start gap-3">
                            <span class="text-green-500 text-xl">✓</span>
                            <div>
                                <p class="font-semibold text-green-800">
                                    Benutzer {{ $record->vorname }} {{ $record->nachname }} wurde erfolgreich im AD angelegt.
                                </p>
                                <p class="text-sm text-green-700 mt-1">Benutzername: <strong>{{ $record->samaccountname }}</strong></p>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="bg-red-50 border border-red-200 rounded-lg p-5">
                    <p class="font-semibold text-red-800">Fehler beim Anlegen des Benutzers</p>
                    @if($record->error_message)
                        <p class="mt-2 text-sm text-red-700">{{ $record->error_message }}</p>
                    @endif
                </div>
            @endif

            {{-- Passwort (nur wenn als Flash vorhanden) --}}
            @if($password)
                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-5"
                     x-data="{ visible: false }">
                    <p class="font-semibold text-yellow-800 mb-3">⚠ Temporäres Passwort (nur einmalig sichtbar)</p>
                    <div class="flex items-center gap-3">
                        <code class="text-lg font-mono bg-white px-4 py-2 rounded border border-yellow-200 tracking-widest"
                              x-show="visible" x-cloak>{{ $password }}</code>
                        <div x-show="!visible" class="text-gray-400 text-sm">••••••••</div>
                        <button type="button" @click="visible = !visible"
                                class="text-xs text-yellow-700 underline hover:text-yellow-900"
                                x-text="visible ? 'Ausblenden' : 'Anzeigen'">Anzeigen</button>
                        <button type="button" x-show="visible" x-cloak
                                @click="navigator.clipboard.writeText('{{ $password }}')"
                                class="text-xs text-yellow-700 underline hover:text-yellow-900">Kopieren</button>
                    </div>
                    <p class="text-xs text-yellow-600 mt-3">
                        Der Benutzer muss das Passwort beim ersten Login ändern.
                        Dieses Passwort ist <strong>nicht in der Datenbank gespeichert</strong>.
                    </p>
                </div>
            @endif

            {{-- Kontodaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Angelegte Kontodaten</h3>
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Name</dt><dd class="text-gray-800">{{ $record->vorname }} {{ $record->nachname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Benutzername</dt><dd class="text-gray-800 font-mono">{{ $record->samaccountname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">UPN / E-Mail</dt><dd class="text-gray-800 font-mono">{{ $record->upn }}</dd></div>
                    @if($record->rufnummer)
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Rufnummer</dt><dd class="text-gray-800">{{ $record->rufnummer }}</dd></div>
                    @endif
                    @if($record->distinguished_name)
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Distinguished Name</dt><dd class="text-gray-600 font-mono text-xs break-all">{{ $record->distinguished_name }}</dd></div>
                    @endif
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Vorlage</dt><dd class="text-gray-800">{{ $record->vorlage?->name ?? '–' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Angelegt von</dt><dd class="text-gray-800">{{ $record->createdBy?->name ?? '–' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Angelegt am</dt><dd class="text-gray-800">{{ $record->created_at->format('d.m.Y H:i') }} Uhr</dd></div>
                </dl>
            </div>

            {{-- Checkliste --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Was wurde gemacht?</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <span class="{{ $record->status === 'erfolgreich' ? 'text-green-500' : 'text-red-400' }}">
                            {{ $record->status === 'erfolgreich' ? '✓' : '✗' }}
                        </span>
                        AD-Benutzerkonto angelegt
                        @if($record->status === 'erfolgreich' && $record->error_message)
                            <span class="text-xs text-amber-600">(deaktiviert – Passwort manuell setzen)</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="{{ $record->welcome_mail_sent_at ? 'text-green-500' : 'text-gray-300' }}">
                            {{ $record->welcome_mail_sent_at ? '✓' : '○' }}
                        </span>
                        Begrüßungsmail an Benutzer
                        @if($record->welcome_mail_sent_at)
                            <span class="text-xs text-gray-400">({{ $record->welcome_mail_sent_at->format('H:i') }})</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="{{ $record->supervisor_mail_sent_at ? 'text-green-500' : 'text-gray-300' }}">
                            {{ $record->supervisor_mail_sent_at ? '✓' : '○' }}
                        </span>
                        Info-Mail an Vorgesetzten
                        @if($record->supervisor_mail_sent_at)
                            <span class="text-xs text-gray-400">({{ $record->supervisor_mail_sent_at->format('H:i') }})</span>
                        @endif
                    </li>
                    @if($record->mailbox_status || (new \App\Modules\Onboarding\Services\ExchangeMailboxService)->isConfigured())
                    <li class="flex items-start gap-2"
                        x-data="{
                            loading: false, msg: null, ok: null,
                            async retry() {
                                this.loading = true; this.msg = null;
                                try {
                                    const r = await fetch('{{ route('onboarding.records.retry-mailbox', $record) }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                                    });
                                    const j = await r.json();
                                    this.ok = j.ok; this.msg = j.message;
                                    if (j.ok) setTimeout(() => location.reload(), 2000);
                                } catch(e) { this.ok = false; this.msg = e.toString(); }
                                finally { this.loading = false; }
                            }
                        }">
                        @if($record->mailbox_status === 'aktiviert')
                            <span class="text-green-500 mt-0.5">✓</span>
                            <span>Exchange-Postfach aktiviert
                                <span class="text-xs text-gray-400">({{ $record->mailbox_enabled_at?->format('H:i') }})</span>
                                @if($record->mailbox_error)
                                    <span class="block text-xs text-gray-500 mt-0.5 font-mono">{{ $record->mailbox_error }}</span>
                                @endif
                            </span>
                        @else
                            <span class="{{ $record->mailbox_status === 'fehler' ? 'text-red-400' : 'text-gray-300' }} mt-0.5">
                                {{ $record->mailbox_status === 'fehler' ? '✗' : '○' }}
                            </span>
                            <span>Exchange-Postfach
                                @if($record->mailbox_status === 'fehler') <span class="text-red-700">konnte nicht aktiviert werden</span>
                                @else noch nicht aktiviert @endif
                                @if($record->mailbox_error)
                                    <span class="block text-xs text-red-600 mt-0.5 font-mono">{{ $record->mailbox_error }}</span>
                                @endif
                                <button type="button" @click="retry()" :disabled="loading"
                                        class="ml-2 text-xs text-indigo-600 underline hover:text-indigo-800 disabled:opacity-50"
                                        x-text="loading ? 'Aktiviere … (bis 30s)' : 'Nochmal versuchen'">
                                    Nochmal versuchen
                                </button>
                                <span x-show="msg !== null" x-cloak :class="ok ? 'text-green-700' : 'text-red-700'"
                                      class="block text-xs mt-1 font-mono" x-text="msg"></span>
                            </span>
                        @endif
                    </li>
                    @endif
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('onboarding.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Nächsten Benutzer anlegen
                </a>
                <a href="{{ route('onboarding.records.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Zur History
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
