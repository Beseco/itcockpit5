<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('onboarding.index') }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800">Onboarding abgeschlossen</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status --}}
            @if($record->status === 'erfolgreich')
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
