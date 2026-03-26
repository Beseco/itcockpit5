<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Abteilung bearbeiten: {{ $abteilung->anzeigename }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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
        </div>
    </div>
</x-app-layout>
