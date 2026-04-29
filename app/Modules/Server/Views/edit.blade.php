<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('server.show', $server) }}" class="text-gray-400 hover:text-gray-600">← Zurück</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Server bearbeiten: {{ $server->name }}</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- LDAP-Info (nur lesend anzeigen) --}}
            @if ($server->ldap_synced)
                <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded text-sm">
                    <strong>LDAP-synchronisierter Server.</strong>
                    Einige Felder (Name, Hostname, OS) werden beim nächsten Sync überschrieben.
                    Letzter Sync: {{ $server->last_sync_at?->format('d.m.Y H:i') ?? '—' }}
                </div>
            @endif

            {{-- Revision-Bereich --}}
            @php
                $revMissing = [];
                if (empty($server->description))      $revMissing[] = 'Beschreibung';
                if (empty($server->doc_url))          $revMissing[] = 'Dokumentations-Link';
                if (empty($server->type))             $revMissing[] = 'Typ (VM / Bare Metal)';
                if (empty($server->operating_system)) $revMissing[] = 'Betriebssystem';
                if (empty($server->role_id))          $revMissing[] = 'Rolle';
                if (empty($server->backup_level_id))  $revMissing[] = 'Backup-Stufe';
                if (empty($server->patch_ring_id))    $revMissing[] = 'Patch-Ring';
                if (empty($server->admin_user_id))    $revMissing[] = 'Verantwortlicher';
                if (empty($server->gruppe_id))        $revMissing[] = 'Gruppe';
                if (empty($server->abteilung_id))     $revMissing[] = 'Abteilung';
            @endphp

            @if (session('revision_error'))
                <div class="mb-4 rounded-md bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-800">
                    {{ session('revision_error') }}
                </div>
            @endif

            <div class="mb-4 bg-white shadow-sm sm:rounded-lg px-5 py-4"
                 x-data="{
                    revOpen: false,
                    checks: { c1: false, c2: false, c3: false, c4: false, c5: false, c6: false, c7: false, c8: false, c9: false, c10: false },
                    get allChecked() { return Object.values(this.checks).every(v => v); }
                 }">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Revisionsdatum:
                        @if ($server->revision_date)
                            <span class="{{ $server->revision_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-900 font-medium' }} ml-1">
                                {{ $server->revision_date->format('d.m.Y') }}
                                @if ($server->revision_date->isPast()) <span class="font-normal text-red-500">(überfällig)</span> @endif
                            </span>
                        @else
                            <span class="text-gray-400 ml-1">—</span>
                        @endif
                    </div>
                    <button type="button" @click="revOpen = true"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                        ✓ Revision durchgeführt
                    </button>
                </div>

                {{-- Revision-Modal --}}
                <div x-show="revOpen"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                     x-transition style="display:none"
                     @keydown.escape.window="revOpen = false">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">

                        @if (count($revMissing) > 0)
                            {{-- Voraussetzungen nicht erfüllt --}}
                            <h3 class="text-lg font-semibold text-red-700 mb-2">Revision nicht möglich</h3>
                            <p class="text-sm text-gray-600 mb-3">
                                Bitte folgende Felder zuerst ausfüllen:
                            </p>
                            <ul class="space-y-1 mb-4">
                                @foreach ($revMissing as $field)
                                    <li class="flex items-center gap-2 text-sm text-red-700">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        {{ $field }}
                                    </li>
                                @endforeach
                            </ul>
                            <div class="flex justify-end">
                                <button @click="revOpen = false"
                                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">
                                    Schließen
                                </button>
                            </div>
                        @else
                            {{-- Checkliste --}}
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">Revision bestätigen</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Bitte bestätigen Sie, dass alle Punkte der Revision geprüft wurden:
                            </p>
                            <div class="space-y-2 mb-5">
                                @foreach ([
                                    'c1'  => 'Beschreibung ist aktuell und vollständig',
                                    'c2'  => 'Dokumentations-Link ist vorhanden und aktuell',
                                    'c3'  => 'Typ (VM / Bare Metal) ist korrekt eingetragen',
                                    'c4'  => 'Betriebssystem ist korrekt und aktuell',
                                    'c5'  => 'Rolle ist korrekt zugewiesen',
                                    'c6'  => 'Backup-Stufe wurde geprüft und ist korrekt',
                                    'c7'  => 'Patch-Ring wurde geprüft und ist korrekt',
                                    'c8'  => 'Verantwortlicher ist korrekt zugeordnet',
                                    'c9'  => 'Gruppe ist korrekt zugeordnet',
                                    'c10' => 'Abteilung ist korrekt zugeordnet',
                                ] as $key => $label)
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" x-model="checks.{{ $key }}"
                                           class="mt-0.5 rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mb-4">
                                Nächste Revision: <strong>{{ now()->addMonths(12)->format('d.m.Y') }}</strong>
                            </p>
                            <div class="flex justify-end gap-3">
                                <button @click="revOpen = false"
                                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">
                                    Abbrechen
                                </button>
                                <form action="{{ route('server.revision-done', $server) }}" method="POST">
                                    @csrf
                                    <button type="submit" :disabled="!allChecked"
                                            :class="allChecked ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                            class="px-4 py-2 text-sm rounded-md font-semibold transition-colors">
                                        Ja, Revision durchgeführt
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- Alpine-Wrapper für Delete-Modal (außerhalb des Update-Forms!) --}}
            <div x-data="{ deleteOpen: false }">

                {{-- Update-Form --}}
                <form action="{{ route('server.update', $server) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('server::_form')
                    <div class="mt-6 flex justify-between">
                        <div>
                            @can('server.delete')
                                <button type="button" @click="deleteOpen = true"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-red-700">
                                    Server löschen
                                </button>
                            @endcan
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('server.show', $server) }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Speichern
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Delete-Modal – AUSSERHALB des Update-Forms --}}
                @can('server.delete')
                    <div x-show="deleteOpen"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                         x-transition style="display:none">
                        <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Server löschen?</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                „<strong>{{ $server->name }}</strong>" wird unwiderruflich gelöscht.
                            </p>
                            <div class="flex justify-end gap-3">
                                <button @click="deleteOpen = false"
                                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">
                                    Abbrechen
                                </button>
                                <form action="{{ route('server.destroy', $server) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-2 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md">
                                        Löschen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan

            </div>{{-- Ende Alpine-Wrapper --}}
        </div>
    </div>
</x-app-layout>
