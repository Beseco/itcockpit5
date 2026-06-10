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
                @php
                    $warnings = $record->error_message ? explode(' | ', $record->error_message) : [];
                    $pwdWarning     = collect($warnings)->first(fn($w) => str_contains($w, 'Passwort'));
                    $homedirWarning = collect($warnings)->first(fn($w) => str_contains($w, 'Heimatverzeichnis'));
                @endphp

                @if($pwdWarning)
                    <div class="bg-amber-50 border border-amber-300 rounded-lg p-5">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-500 text-xl">⚠</span>
                            <div>
                                <p class="font-semibold text-amber-800">
                                    Benutzer {{ $record->vorname }} {{ $record->nachname }} wurde im AD angelegt – Konto ist noch deaktiviert.
                                </p>
                                <p class="text-sm text-amber-700 mt-2">
                                    <strong>Passwort konnte nicht gesetzt werden:</strong> {{ $pwdWarning }}
                                </p>
                                <div class="mt-3 p-3 bg-amber-100 rounded text-sm text-amber-800">
                                    <strong>Nächste Schritte:</strong>
                                    <ol class="mt-1 ml-4 list-decimal space-y-1">
                                        <li>Passwort in ADUC manuell setzen und Konto aktivieren</li>
                                        <li>Oder: LDAPS in den <a href="{{ route('adusers.settings') }}" class="underline">AD-Einstellungen</a> aktivieren (Port 636)</li>
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

                {{-- Heimatverzeichnis-Warnung für ältere Records ohne creation_log --}}
                @if($homedirWarning && !$record->creation_log)
                    <div class="bg-orange-50 border border-orange-300 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <span class="text-orange-500 text-lg">⚠</span>
                            <div>
                                <p class="font-semibold text-orange-800 text-sm">Heimatverzeichnis nicht angelegt</p>
                                <p class="text-sm text-orange-700 mt-1">{{ $homedirWarning }}</p>
                                <p class="text-xs text-orange-600 mt-2">Bitte den Ordner manuell auf dem Fileserver anlegen oder die SMB-Zugangsdaten in den <a href="{{ route('onboarding.settings') }}" class="underline">Onboarding-Einstellungen</a> prüfen.</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Anlage-Log --}}
                @if($record->creation_log)
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Protokoll – Anlage</h3>
                        <ol class="space-y-3">
                            @foreach($record->creation_log as $step)
                                @php
                                    $skipped  = $step['skipped'] ?? false;
                                    $aclError = $step['acl_error'] ?? '';
                                    $ok       = !$skipped && $step['success'] && empty($aclError);
                                    $warn     = !$skipped && $step['success'] && !empty($aclError);
                                    $fail     = !$skipped && !$step['success'];
                                @endphp
                                <li class="flex items-start gap-3">
                                    {{-- Icon --}}
                                    <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold
                                        @if($ok) bg-green-100 text-green-700
                                        @elseif($warn) bg-amber-100 text-amber-700
                                        @elseif($fail) bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-400
                                        @endif">
                                        @if($ok) ✓ @elseif($warn) ! @elseif($fail) ✕ @else — @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium
                                            @if($ok) text-gray-800
                                            @elseif($warn) text-amber-800
                                            @elseif($fail) text-red-700
                                            @else text-gray-400
                                            @endif">
                                            {{ $step['label'] }}
                                            @if($skipped)
                                                <span class="ml-1 text-xs font-normal text-gray-400">(übersprungen – nicht konfiguriert)</span>
                                            @endif
                                        </p>
                                        @if(!empty($step['detail']))
                                            <p class="text-xs mt-0.5
                                                @if($fail) text-red-600 @else text-gray-500 @endif">
                                                {{ $step['detail'] }}
                                            </p>
                                            @if($fail && $step['step'] === 'home_dir')
                                                <p class="text-xs text-orange-600 mt-1">
                                                    → SMB-Zugangsdaten in den <a href="{{ route('onboarding.settings') }}" class="underline">Onboarding-Einstellungen</a> prüfen oder Ordner manuell anlegen.
                                                </p>
                                            @endif
                                        @endif
                                        {{-- ACL-Warnung: Ordner existiert, aber Berechtigungen fehlen --}}
                                        @if(!empty($step['acl_error']))
                                            <p class="text-xs mt-1 text-amber-700 font-medium">
                                                ⚠ Ordner erstellt, aber Benutzerberechtigungen konnten nicht gesetzt werden:
                                            </p>
                                            <p class="text-xs text-amber-600 font-mono break-all">{{ $step['acl_error'] }}</p>
                                            <p class="text-xs text-amber-600 mt-1">
                                                → Bitte Ordner in Windows manuell über Sicherheitseinstellungen für <strong>{{ $record->samaccountname }}</strong> freigeben (Vollzugriff).
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
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

            {{-- Temporäres Passwort (nur wenn als Flash vorhanden) --}}
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
                        Dieses temporäre Passwort erfordert <strong>keine</strong> sofortige Änderung.
                        Das endgültige Passwort (mit Änderungspflicht) wird nach Abschluss der Todo-Liste vergeben.
                        Die gleiche Information wurde per E-Mail an Sie gesendet.
                    </p>
                </div>
            @endif

            {{-- Todo-Liste Link --}}
            @if($record->isSetupPhase())
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5">
                    <p class="font-semibold text-indigo-800 mb-2">Nächste Schritte – Todo-Liste</p>
                    <p class="text-sm text-indigo-700 mb-4">
                        Bevor das Onboarding abgeschlossen werden kann, müssen folgende Aufgaben erledigt werden:
                        E-Mail-Postfach anlegen, Laufwerke prüfen, Outlook und Fachverfahren einrichten.
                    </p>
                    <a href="{{ route('onboarding.todo.show', $record->todo_token) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Todo-Liste öffnen &rarr;
                    </a>
                </div>
            @elseif($record->phase === 'completed')
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
                    ✓ Onboarding abgeschlossen am {{ $record->completed_at?->format('d.m.Y H:i') }} Uhr
                </div>
            @endif

            {{-- Kontodaten --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Angelegte Kontodaten</h3>
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Name</dt><dd>{{ $record->vorname }} {{ $record->nachname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Benutzername</dt><dd class="font-mono">{{ $record->samaccountname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">UPN / E-Mail</dt><dd class="font-mono">{{ $record->upn }}</dd></div>
                    @if($record->rufnummer)
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Rufnummer</dt><dd>{{ $record->rufnummer }}</dd></div>
                    @endif
                    @if($record->distinguished_name)
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Distinguished Name</dt><dd class="text-gray-600 font-mono text-xs break-all">{{ $record->distinguished_name }}</dd></div>
                    @endif
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Vorlage</dt><dd>{{ $record->vorlage?->name ?? '–' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Angelegt von</dt><dd>{{ $record->createdBy?->name ?? '–' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Angelegt am</dt><dd>{{ $record->created_at->format('d.m.Y H:i') }} Uhr</dd></div>
                </dl>
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
