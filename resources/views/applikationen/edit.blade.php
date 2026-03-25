<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Applikation bearbeiten: {{ $app->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('applikationen.update', $app) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('applikationen._form')
                        <div class="flex items-center justify-between mt-6">
                            <div>
                                @can('applikationen.delete')
                                    {{-- Delete-Formular AUSSERHALB des Update-Formulars via JS-Submit --}}
                                    <button type="button"
                                            onclick="if(confirm('Applikation wirklich löschen?')) document.getElementById('delete-form-{{ $app->id }}').submit()"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        Löschen
                                    </button>
                                @endcan
                            </div>
                            <div class="flex gap-3">
                                <a href="{{ route('applikationen.index') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    Abbrechen
                                </a>
                                <x-primary-button>Speichern</x-primary-button>
                            </div>
                        </div>
                    </form>

                    @can('applikationen.delete')
                    <form id="delete-form-{{ $app->id }}"
                          action="{{ route('applikationen.destroy', $app) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
