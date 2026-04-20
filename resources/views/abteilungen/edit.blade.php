<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Abteilung bearbeiten: {{ $abteilung->anzeigename }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                     class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('abteilungen.update', $abteilung) }}">
                    @csrf
                    @method('PUT')
                    @include('abteilungen._form')
                    <div class="flex items-center justify-between mt-6">
                        @can('abteilungen.delete')
                        <button type="button"
                                onclick="if(confirm('Abteilung »{{ addslashes($abteilung->anzeigename) }}« wirklich löschen?')) document.getElementById('delete-form-{{ $abteilung->id }}').submit()"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-red-700">
                            Löschen
                        </button>
                        @endcan
                        <div class="flex items-center gap-3 ml-auto">
                            <a href="{{ route('abteilungen.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Abbrechen
                            </a>
                            <x-primary-button type="submit">Speichern</x-primary-button>
                        </div>
                    </div>
                </form>

                {{-- Delete-Form AUSSERHALB der Update-Form --}}
                @can('abteilungen.delete')
                <form id="delete-form-{{ $abteilung->id }}"
                      action="{{ route('abteilungen.destroy', $abteilung) }}" method="POST" class="hidden">
                    @csrf @method('DELETE')
                </form>
                @endcan

            </div>

            {{-- Revisionsmail-Test --}}
            @can('abteilungen.edit')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-1">Test-Revisionsmail</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Sendet eine Revisions-E-Mail mit der aktuellen Applikationsliste dieser Abteilung
                            an Ihre eigene Adresse (<strong>{{ auth()->user()->email }}</strong>).
                            @if($abteilung->vorgesetzter)
                                Vorgesetzter: <strong>{{ $abteilung->vorgesetzter->anzeigename }}</strong>.
                            @endif
                            @if($abteilung->stellvertreter)
                                Stellvertreter: <strong>{{ $abteilung->stellvertreter->anzeigename }}</strong>.
                            @endif
                        </p>
                        @if($abteilung->revision_notified_at)
                            <p class="text-xs text-gray-400 mt-1">
                                Zuletzt gesendet: {{ $abteilung->revision_notified_at->format('d.m.Y H:i') }} Uhr
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('abteilungen.revision-mail-test', $abteilung) }}" class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-semibold text-white hover:bg-indigo-700 whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Test-Mail an mich senden
                        </button>
                    </form>
                </div>
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
