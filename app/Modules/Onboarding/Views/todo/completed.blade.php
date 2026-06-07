<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('onboarding.records.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800">Onboarding abgeschlossen</h2>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-start gap-3">
                    <span class="text-green-500 text-2xl">✓</span>
                    <div>
                        <p class="font-semibold text-green-800 text-lg">
                            Onboarding für {{ $record->vorname }} {{ $record->nachname }} abgeschlossen!
                        </p>
                        <p class="text-sm text-green-700 mt-1">
                            Das finale Passwort wurde gesetzt. Der Benutzer und der Vorgesetzte wurden per E-Mail benachrichtigt.
                        </p>
                    </div>
                </div>
            </div>

            @if($finalPassword)
                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-5"
                     x-data="{ visible: false }">
                    <p class="font-semibold text-yellow-800 mb-3">⚠ Finales Passwort (nur einmalig sichtbar)</p>
                    <p class="text-xs text-yellow-600 mb-3">
                        Der Benutzer hat das Passwort bereits per E-Mail erhalten und muss es beim ersten Login ändern.
                    </p>
                    <div class="flex items-center gap-3">
                        <code class="text-lg font-mono bg-white px-4 py-2 rounded border border-yellow-200 tracking-widest"
                              x-show="visible" x-cloak>{{ $finalPassword }}</code>
                        <div x-show="!visible" class="text-gray-400 text-sm">••••••••</div>
                        <button type="button" @click="visible = !visible"
                                class="text-xs text-yellow-700 underline hover:text-yellow-900"
                                x-text="visible ? 'Ausblenden' : 'Anzeigen'">Anzeigen</button>
                        <button type="button" x-show="visible" x-cloak
                                @click="navigator.clipboard.writeText('{{ $finalPassword }}')"
                                class="text-xs text-yellow-700 underline hover:text-yellow-900">Kopieren</button>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Übersicht</h3>
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Name</dt><dd>{{ $record->vorname }} {{ $record->nachname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Benutzername</dt><dd class="font-mono">{{ $record->samaccountname }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">E-Mail</dt><dd class="font-mono">{{ $record->upn }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Begrüßungsmail</dt>
                        <dd>{{ $record->welcome_mail_sent_at ? '✓ versendet ' . $record->welcome_mail_sent_at->format('H:i') : '○ nicht versendet' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Vorgesetzten-Mail</dt>
                        <dd>{{ $record->supervisor_mail_sent_at ? '✓ versendet ' . $record->supervisor_mail_sent_at->format('H:i') : '○ nicht versendet' }}</dd></div>
                    <div class="flex py-2 gap-4"><dt class="w-44 font-medium text-gray-500">Abgeschlossen am</dt>
                        <dd>{{ $record->completed_at?->format('d.m.Y H:i') }} Uhr</dd></div>
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
