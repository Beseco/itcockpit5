<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Neue Abteilung anlegen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('abteilungen.store') }}">
                    @csrf
                    @include('abteilungen._form')
                    <div class="flex items-center justify-end mt-6 gap-3">
                        <a href="{{ route('abteilungen.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Abbrechen
                        </a>
                        <x-primary-button type="submit">Speichern</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
