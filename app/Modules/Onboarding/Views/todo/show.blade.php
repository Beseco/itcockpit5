<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('onboarding.records.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Onboarding</a>
            <span class="text-gray-300">/</span>
            <h2 class="font-semibold text-xl text-gray-800">Einrichtungs-Checkliste</h2>
        </div>
    </x-slot>

    @include('adusers::_subnav')

    @php
        $todoDefs   = \App\Modules\Onboarding\Models\OnboardingRecord::TODOS;
        $doneTodos  = $record->todos ?? [];
        $checkUrl   = route('onboarding.todo.check', $record->todo_token);
        $mailTestUrl = route('onboarding.todo.mail-test', $record->todo_token);
        $completeUrl = route('onboarding.todo.complete', $record->todo_token);
    @endphp

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Info --}}
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-5">
                <p class="font-semibold text-indigo-800">
                    Einrichtungs-Checkliste für {{ $record->vorname }} {{ $record->nachname }}
                </p>
                <p class="text-sm text-indigo-600 mt-1">
                    Benutzername: <span class="font-mono">{{ $record->samaccountname }}</span>
                    &nbsp;·&nbsp; E-Mail: <span class="font-mono">{{ $record->upn }}</span>
                </p>
            </div>

            {{-- Error / Info flash --}}
            @if(session('error'))
                <div class="bg-red-50 border border-red-300 rounded-lg p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">{{ session('info') }}</div>
            @endif

            {{-- Completed banner --}}
            @if($record->phase === 'completed')
                <div class="bg-green-50 border border-green-200 rounded-lg p-5">
                    <p class="font-semibold text-green-800">✓ Onboarding abgeschlossen</p>
                    <p class="text-sm text-green-700 mt-1">Abgeschlossen am {{ $record->completed_at?->format('d.m.Y H:i') }} Uhr</p>
                </div>
            @endif

            {{-- Todo-Checkliste --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6"
                 x-data="todoList()"
                 x-init="init()">

                <h3 class="text-sm font-semibold text-gray-700 mb-4">Aufgaben</h3>

                <ul class="space-y-3">
                    @foreach($todoDefs as $key => $def)
                        @php $isDone = in_array($key, $doneTodos, true); @endphp
                        <li class="flex items-start gap-3 py-1" x-bind:class="todos.includes('{{ $key }}') ? 'opacity-100' : ''">
                            <button type="button"
                                    @click="toggle('{{ $key }}')"
                                    :disabled="completing || '{{ $record->phase }}' === 'completed'"
                                    class="mt-0.5 w-5 h-5 rounded border-2 flex-shrink-0 flex items-center justify-center transition-colors
                                           disabled:cursor-not-allowed"
                                    :class="todos.includes('{{ $key }}')
                                        ? 'bg-green-500 border-green-500 text-white'
                                        : 'border-gray-300 hover:border-green-400'">
                                <svg x-show="todos.includes('{{ $key }}')" class="w-3 h-3" fill="none" viewBox="0 0 12 12">
                                    <path d="M1 6l3.5 3.5L11 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div class="flex-1">
                                <span :class="todos.includes('{{ $key }}') ? 'line-through text-gray-400' : 'text-gray-700'"
                                      class="text-sm">{{ $def['label'] }}</span>

                                @if(($def['auto_clear_home'] ?? false) && ($record->ad_attributes_snapshot['heimatverzeichnis'] ?? null))
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Pfad: <span class="font-mono">{{ $record->ad_attributes_snapshot['heimatverzeichnis'] ?? '' }}</span>
                                    </p>
                                    <div x-show="todos.includes('{{ $key }}')" x-cloak
                                         class="mt-1 text-xs text-green-700">
                                        ✓ homeDirectory/homeDrive werden automatisch aus dem AD-Profil entfernt – GPO übernimmt.
                                    </div>
                                @endif
                                @if($def['mail_test'] ?? false)
                                    <div x-show="todos.includes('{{ $key }}')" x-cloak class="mt-2 flex items-center gap-3 flex-wrap">
                                        <button type="button"
                                                @click="sendMailTest()"
                                                :disabled="mailTestSending || completing || '{{ $record->phase }}' === 'completed'"
                                                class="inline-flex items-center px-3 py-1 bg-indigo-50 border border-indigo-300 rounded text-xs text-indigo-700 hover:bg-indigo-100 disabled:opacity-50">
                                            <span x-text="mailTestSending ? 'Sende …' : 'Test-Mail senden'">Test-Mail senden</span>
                                        </button>
                                        <span x-show="mailTestMsg" x-cloak
                                              :class="mailTestOk ? 'text-green-700' : 'text-red-700'"
                                              class="text-xs" x-text="mailTestMsg"></span>
                                        <span x-show="verified" x-cloak class="inline-flex items-center gap-1 text-xs text-green-700 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            E-Mail verifiziert
                                        </span>
                                        <span x-show="!verified && mailTestSent" x-cloak class="text-xs text-amber-600">
                                            Warte auf Bestätigung durch Empfänger …
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Abschließen --}}
                @if($record->phase !== 'completed')
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <div x-show="!canComplete" x-cloak class="text-sm text-gray-400 italic">
                        Alle Aufgaben abhaken und E-Mail bestätigen, um den Vorgang abzuschließen.
                    </div>
                    <form x-show="canComplete" x-cloak action="{{ $completeUrl }}" method="POST" @submit="completing = true">
                        @csrf
                        <button type="submit"
                                :disabled="completing"
                                class="inline-flex items-center px-5 py-2.5 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-green-700 disabled:opacity-60">
                            <span x-text="completing ? 'Wird abgeschlossen …' : 'Onboarding abschließen'">Onboarding abschließen</span>
                        </button>
                        <p class="mt-2 text-xs text-gray-500">
                            Dadurch wird das endgültige Passwort vergeben, der Benutzer und der Vorgesetzte erhalten eine E-Mail.
                        </p>
                    </form>
                </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    function todoList() {
        return {
            todos:          @json($doneTodos),
            verified:       {{ $record->mail_verified_at ? 'true' : 'false' }},
            mailTestSending: false,
            mailTestSent:   false,
            mailTestMsg:    null,
            mailTestOk:     null,
            completing:     false,

            get canComplete() {
                const allKeys = @json(array_keys($todoDefs));
                return allKeys.every(k => this.todos.includes(k)) && this.verified;
            },

            init() {
                // Polling für Mail-Verifikation (alle 5 Sekunden, wenn Test-Mail gesendet)
                setInterval(() => {
                    if (this.mailTestSent && !this.verified) {
                        this.checkVerification();
                    }
                }, 5000);
            },

            async toggle(key) {
                const resp = await fetch('{{ $checkUrl }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ key }),
                });
                const data = await resp.json();
                if (data.ok) {
                    this.todos    = data.todos;
                    this.verified = data.verified;
                }
            },

            async sendMailTest() {
                this.mailTestSending = true;
                this.mailTestMsg     = null;
                try {
                    const resp = await fetch('{{ $mailTestUrl }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await resp.json();
                    this.mailTestOk   = data.ok;
                    this.mailTestMsg  = data.message;
                    this.mailTestSent = data.ok;
                } catch (e) {
                    this.mailTestOk  = false;
                    this.mailTestMsg = e.toString();
                } finally {
                    this.mailTestSending = false;
                }
            },

            async checkVerification() {
                try {
                    const resp = await fetch('{{ route('onboarding.todo.status', $record->todo_token) }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await resp.json();
                    this.verified = data.verified;
                } catch {}
            },
        };
    }
    </script>
    @endpush

</x-app-layout>
