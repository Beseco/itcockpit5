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
